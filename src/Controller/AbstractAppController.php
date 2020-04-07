<?php

namespace App\Controller;

use App\Manager\CallManager;
use App\Manager\PaginatorManager;
use App\Model\CallRequestModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAppController extends AbstractController
{
    /**
     * @required
     */
    public function setCallManager(CallManager $callManager)
    {
        $this->callManager = $callManager;
    }

    /**
     * @required
     */
    public function setPaginatorManager(PaginatorManager $paginatorManager)
    {
        $this->paginatorManager = $paginatorManager;
    }

    public function renderAbstract(Request $request, string $view, array $parameters = [], Response $response = null): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_cluster/health');
        $clusterHealth = $this->callManager->call($callRequest);

        $parameters['clusterHealth'] = $clusterHealth;

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_cat/master');
        $master = $this->callManager->call($callRequest);

        $parameters['master_node'] = $master[0]['node'] ?? false;

        /*if (null === $response) {
            $response = new Response();
        }
        $response->headers->set('Symfony-Debug-Toolbar-Replace', 1);*/

        return $this->render($view, $parameters, $response);
    }
}
