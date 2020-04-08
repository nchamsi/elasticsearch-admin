<?php

namespace App\Controller;

use App\Controller\AbstractAppController;
use App\Exception\CallException;
use App\Form\CreateAliasType;
use App\Form\CreateIndexType;
use App\Form\ReindexType;
use App\Manager\ElasticsearchIndexManager;
use App\Model\CallRequestModel;
use App\Model\ElasticsearchIndexModel;
use App\Model\ElasticsearchIndexAliasModel;
use App\Model\ElasticsearchReindexModel;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/admin")
 */
class IndexController extends AbstractAppController
{
    public function __construct(ElasticsearchIndexManager $elasticsearchIndexManager)
    {
        $this->elasticsearchIndexManager = $elasticsearchIndexManager;
    }

    /**
     * @Route("/indices", name="indices")
     */
    public function index(Request $request): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_cat/indices');
        $callRequest->setQuery(['s' => 'index', 'h' => 'index,docs.count,docs.deleted,pri.store.size,store.size,status,health,pri,rep,creation.date.string,sth']);
        $callResponse = $this->callManager->call($callRequest);
        $indices = $callResponse->getContent();

        return $this->renderAbstract($request, 'Modules/index/index_index.html.twig', [
            'indices' => $this->paginatorManager->paginate([
                'route' => 'indices',
                'route_parameters' => [],
                'total' => count($indices),
                'rows' => $indices,
                'page' => 1,
                'size' => count($indices),
            ]),
        ]);
    }

    /**
     * @Route("/indices/force/merge", name="indices_force_merge_all")
     */
    public function forceMergeAll(Request $request): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/_forcemerge');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_force_merge_all');

        return $this->redirectToRoute('indices', []);
    }

    /**
     * @Route("/indices/cache/clear", name="indices_cache_clear_all")
     */
    public function cacheClearAll(Request $request): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/_cache/clear');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_cache_clear_all');

        return $this->redirectToRoute('indices', []);
    }

    /**
     * @Route("/indices/flush", name="indices_flush_all")
     */
    public function flushAll(Request $request): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/_flush');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_flush_all');

        return $this->redirectToRoute('indices', []);
    }

    /**
     * @Route("/indices/refresh", name="indices_refresh_all")
     */
    public function refreshAll(Request $request): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/_refresh');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_refresh_all');

        return $this->redirectToRoute('indices', []);
    }

    /**
     * @Route("/indices/reindex", name="indices_reindex")
     */
    public function reindex(Request $request): Response
    {
        $indices = $this->elasticsearchIndexManager->selectIndices();

        $reindexModel = new ElasticsearchReindexModel();
        if ($request->query->get('index')) {
            $reindexModel->setSource($request->query->get('index'));
        }
        $form = $this->createForm(ReindexType::class, $reindexModel, ['indices' => $indices]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $json = [
                    'source' => [
                        'index' => $reindexModel->getSource(),
                    ],
                    'dest' => [
                        'index' => $reindexModel->getDestination(),
                    ],
                ];
                $callRequest = new CallRequestModel();
                $callRequest->setMethod('POST');
                $callRequest->setPath('/_reindex');
                $callRequest->setJson($json);
                $this->callManager->call($callRequest);

                $this->addFlash('success', 'success.indices_reindex');

                return $this->redirectToRoute('indices_read', ['index' => $reindexModel->getDestination()]);
            } catch (CallException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->renderAbstract($request, 'Modules/index/index_reindex.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/indices/create", name="indices_create")
     */
    public function create(Request $request): Response
    {
        $indexModel = new ElasticsearchIndexModel();
        $form = $this->createForm(CreateIndexType::class, $indexModel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $json = [];
                if ($indexModel->getSettings()) {
                    $json['settings'] = json_decode($indexModel->getSettings(), true);
                }
                if ($indexModel->getMappings()) {
                    $json['mappings'] = json_decode($indexModel->getMappings(), true);
                }
                $callRequest = new CallRequestModel();
                $callRequest->setMethod('PUT');
                $callRequest->setPath('/'.$indexModel->getName());
                $callRequest->setJson($json);
                $this->callManager->call($callRequest);

                $this->addFlash('success', 'success.indices_create');

                return $this->redirectToRoute('indices_read', ['index' => $indexModel->getName()]);
            } catch (CallException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->renderAbstract($request, 'Modules/index/index_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/indices/{index}", name="indices_read")
     */
    public function read(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        return $this->renderAbstract($request, 'Modules/index/index_read.html.twig', [
            'index' => $index,
        ]);
    }

    /**
     * @Route("/indices/{index}/update", name="indices_update")
     */
    public function update(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        $indexModel = new ElasticsearchIndexModel();
        $indexModel->convert($index);
        $form = $this->createForm(CreateIndexType::class, $indexModel, ['update' => true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($indexModel->getMappings()) {
                    $json = json_decode($indexModel->getMappings(), true);
                    $callRequest = new CallRequestModel();
                    $callRequest->setMethod('PUT');
                    $callRequest->setPath('/'.$indexModel->getName().'/_mapping');
                    $callRequest->setJson($json);
                    $this->callManager->call($callRequest);
                }

                $this->addFlash('success', 'success.indices_update');

                return $this->redirectToRoute('indices_read', ['index' => $indexModel->getName()]);
            } catch (CallException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->renderAbstract($request, 'Modules/index/index_update.html.twig', [
            'index' => $index,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/indices/{index}/settings", name="indices_read_settings")
     */
    public function settings(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        return $this->renderAbstract($request, 'Modules/index/index_read_settings.html.twig', [
            'index' => $index,
        ]);
    }

    /**
     * @Route("/indices/{index}/mappings", name="indices_read_mappings")
     */
    public function mappings(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        return $this->renderAbstract($request, 'Modules/index/index_read_mappings.html.twig', [
            'index' => $index,
        ]);
    }

    /**
     * @Route("/indices/{index}/shards", name="indices_read_shards")
     */
    public function shards(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_cat/shards/'.$index['index']);
        $callRequest->setQuery(['s' => 'shard,prirep', 'h' => 'shard,prirep,state,unassigned.reason,docs,store,node']);
        $callResponse = $this->callManager->call($callRequest);
        $shards = $callResponse->getContent();

        return $this->renderAbstract($request, 'Modules/index/index_read_shards.html.twig', [
            'index' => $index,
            'shards' => $this->paginatorManager->paginate([
                'route' => 'indices_read_shards',
                'route_parameters' => ['index' => $index['index']],
                'total' => count($shards),
                'rows' => $shards,
                'page' => 1,
                'size' => count($shards),
            ]),
        ]);
    }

    /**
     * @Route("/indices/{index}/aliases", name="indices_read_aliases")
     */
    public function aliases(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/'.$index['index'].'/_alias');
        $callResponse = $this->callManager->call($callRequest);
        $aliases = $callResponse->getContent();
        $aliases = array_keys($aliases[$index['index']]['aliases']);

        return $this->renderAbstract($request, 'Modules/index/index_read_aliases.html.twig', [
            'index' => $index,
            'aliases' => $this->paginatorManager->paginate([
                'route' => 'indices_read_aliases',
                'route_parameters' => ['index' => $index['index']],
                'total' => count($aliases),
                'rows' => $aliases,
                'page' => 1,
                'size' => count($aliases),
            ]),
        ]);
    }

    /**
     * @Route("/indices/{index}/aliases/create", name="indices_aliases_create")
     */
    public function createAlias(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        $aliasModel = new ElasticsearchIndexAliasModel();
        $form = $this->createForm(CreateAliasType::class, $aliasModel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $callRequest = new CallRequestModel();
                $callRequest->setMethod('PUT');
                $callRequest->setPath('/'.$index['index'].'/_alias/'.$aliasModel->getName());
                $this->callManager->call($callRequest);

                $this->addFlash('success', 'success.indices_aliases_create');

                return $this->redirectToRoute('indices_read_aliases', ['index' => $index['index']]);
            } catch (CallException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->renderAbstract($request, 'Modules/index/index_read_aliases_create.html.twig', [
            'index' => $index,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("indices/{index}/aliases/{alias}/delete", name="indices_aliases_delete")
     */
    public function deleteAlias(Request $request, string $index, string $alias): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('DELETE');
        $callRequest->setPath('/'.$index.'/_alias/'.$alias);
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_aliases_delete');

        return $this->redirectToRoute('indices_read_aliases', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/documents", name="indices_read_documents")
     */
    public function documents(Request $request, string $index): Response
    {
        $index = $this->elasticsearchIndexManager->getIndex($index);

        if (false == $index) {
            throw new NotFoundHttpException();
        }

        $size = 100;
        $query = [
            'sort' => '_id:desc',
            'size' => $size,
            'from' => ($size * $request->query->get('page', 1)) - $size,
        ];
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/'.$index['index'].'/_search');
        $callRequest->setQuery($query);
        $callResponse = $this->callManager->call($callRequest);
        $documents = $callResponse->getContent();

        if (true == isset($documents['hits']['total']['value'])) {
            $total = $documents['hits']['total']['value'];
            if ('eq' != $documents['hits']['total']['relation']) {
                $this->addFlash('info', 'lower_bound_of_the_total');
            }
        } else {
            $total = $documents['hits']['total'];
        }

        return $this->renderAbstract($request, 'Modules/index/index_read_documents.html.twig', [
            'index' => $index,
            'documents' => $this->paginatorManager->paginate([
                'route' => 'indices_read_documents',
                'route_parameters' => ['index' => $index['index']],
                'total' => $total,
                'rows' => $documents['hits']['hits'],
                'page' => $request->query->get('page', 1),
                'size' => $size,
            ]),
        ]);
    }

    /**
     * @Route("/indices/{index}/delete", name="indices_delete")
     */
    public function delete(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('DELETE');
        $callRequest->setPath('/'.$index);
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_delete');

        return $this->redirectToRoute('indices', []);
    }

    /**
     * @Route("/indices/{index}/close", name="indices_close")
     */
    public function close(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_close');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_close');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/open", name="indices_open")
     */
    public function open(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_open');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_open');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/freeze", name="indices_freeze")
     */
    public function freeze(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_freeze');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_freeze');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/unfreeze", name="indices_unfreeze")
     */
    public function unfreeze(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_unfreeze');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_unfreeze');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/force/merge", name="indices_force_merge")
     */
    public function forceMerge(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_forcemerge');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_force_merge');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/cache/clear", name="indices_cache_clear")
     */
    public function cacheClear(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_cache/clear');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_cache_clear');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/flush", name="indices_flush")
     */
    public function flush(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_flush');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_flush');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }

    /**
     * @Route("/indices/{index}/refresh", name="indices_refresh")
     */
    public function refresh(Request $request, string $index): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setMethod('POST');
        $callRequest->setPath('/'.$index.'/_refresh');
        $this->callManager->call($callRequest);

        $this->addFlash('success', 'success.indices_refresh');

        return $this->redirectToRoute('indices_read', ['index' => $index]);
    }
}