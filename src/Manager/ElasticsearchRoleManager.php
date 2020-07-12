<?php

namespace App\Manager;

use App\Manager\AbstractAppManager;
use App\Manager\CallManager;
use App\Model\CallRequestModel;
use App\Model\CallResponseModel;
use App\Model\ElasticsearchRoleModel;
use Symfony\Component\HttpFoundation\Response;

class ElasticsearchRoleManager extends AbstractAppManager
{
    public function getByName(string $name): ?ElasticsearchRoleModel
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_security/role/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
            $roleModel = null;
        } else {
            $role = $callResponse->getContent();
            $roleNice = $role[key($role)];
            $roleNice['name'] = key($role);

            $roleModel = new ElasticsearchRoleModel();
            $roleModel->convert($roleNice);
        }

        return $roleModel;
    }

    public function getAll(): array
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_ingest/pipeline');
        $callResponse = $this->callManager->call($callRequest);
        $results = $callResponse->getContent();

        $pipelines = [];
        foreach ($results as $k => $row) {
            $row['name'] = $k;
            $roleModel = new ElasticsearchRoleModel();
            $roleModel->convert($row);
            $pipelines[] = $roleModel;
        }

        return $pipelines;
    }

    public function send(ElasticsearchRoleModel $roleModel): CallResponseModel
    {
        $json = $roleModel->getJson();
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('PUT');
        $callRequest->setPath('/_security/role/'.$roleModel->getName());
        $callRequest->setJson($json);

        return $this->callManager->call($callRequest);
    }

    public function deleteByName(string $name): CallResponseModel
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('DELETE');
        $callRequest->setPath('/_security/role/'.$name);

        return $this->callManager->call($callRequest);
    }

    public function selectRoles()
    {
        $roles = [];

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_security/role');
        $callResponse = $this->callManager->call($callRequest);
        $rows = $callResponse->getContent();

        foreach ($rows as $k => $row) {
            $roles[] = $k;
        }

        sort($roles);

        return $roles;
    }

    public function getPrivileges()
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_security/privilege/_builtin');
        $callResponse = $this->callManager->call($callRequest);

        return $callResponse->getContent();
    }
}
