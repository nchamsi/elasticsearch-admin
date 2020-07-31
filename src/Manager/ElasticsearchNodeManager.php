<?php

namespace App\Manager;

use App\Manager\AbstractAppManager;
use App\Manager\CallManager;
use App\Model\CallRequestModel;
use App\Model\CallResponseModel;
use App\Model\ElasticsearchNodeModel;
use Symfony\Component\HttpFoundation\Response;

class ElasticsearchNodeManager extends AbstractAppManager
{
    public function getByName(string $name): ?ElasticsearchNodeModel
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_nodes/'.$name);
        $callResponse = $this->callManager->call($callRequest);
        $node = $callResponse->getContent();

        if (false == isset($node['nodes'][key($node['nodes'])])) {
            $nodeModel = null;
        } else {
            $id = key($node['nodes']);
            $node = $node['nodes'][$id];
            $node['id'] = $id;

            $nodeModel = new ElasticsearchNodeModel();
            $nodeModel->convert($node);
        }

        return $nodeModel;
    }

    public function getAll($sort = 'name:asc'): array
    {
        $nodes = [];

        $query = ['bytes' => 'b', 'h' => 'name,disk.used_percent,ram.percent,cpu,uptime,master,disk.total,disk.used,ram.current,ram.max,heap.percent,heap.max,heap.current'];
        if (true == $this->callManager->hasFeature('cat_sort')) {
            $query['s'] = $sort;
        }
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_cat/nodes');
        $callRequest->setQuery($query);
        $callResponse = $this->callManager->call($callRequest);
        $nodes1 = $callResponse->getContent();

        foreach ($nodes1 as $node) {
            $nodes[$node['name']] = $node;
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_nodes');
        $callResponse = $this->callManager->call($callRequest);
        $nodes2 = $callResponse->getContent();

        foreach ($nodes2['nodes'] as $node) {
            $nodes[$node['name']] = array_merge($node, $nodes[$node['name']]);
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_nodes/stats');
        $callResponse = $this->callManager->call($callRequest);
        $nodes3 = $callResponse->getContent();

        foreach ($nodes3['nodes'] as $node) {
            $nodes[$node['name']]['stats'] = $node;
        }

        return $nodes;
    }

    public function selectNodes(): array
    {
        $nodes = [];

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_nodes');
        $callResponse = $this->callManager->call($callRequest);
        $rows = $callResponse->getContent();

        foreach ($rows['nodes'] as $k => $row) {
            $nodes[$k] = $row['name'];
        }

        return $nodes;
    }
}
