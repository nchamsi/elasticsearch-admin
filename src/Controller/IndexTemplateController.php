<?php

namespace App\Controller;

use App\Controller\AbstractAppController;
use App\Exception\CallException;
use App\Form\CreateIndexTemplateType;
use App\Model\CallRequestModel;
use App\Model\ElasticsearchIndexTemplateModel;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Route("/admin")
 */
class IndexTemplateController extends AbstractAppController
{
    /**
     * @Route("/index-templates", name="index_templates")
     */
    public function index(Request $request): Response
    {
        if (false == $this->checkVersion('7.8')) {
            throw new AccessDeniedHttpException();
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_index_template');
        $callResponse = $this->callManager->call($callRequest);
        $indexTemplates = $callResponse->getContent();

        $indexTemplates = $indexTemplates['index_templates'];

        usort($indexTemplates, [$this, 'sortByName']);

        return $this->renderAbstract($request, 'Modules/index_template/index_template_index.html.twig', [
            'indexTemplates' => $this->paginatorManager->paginate([
                'route' => 'index_templates',
                'route_parameters' => [],
                'total' => count($indexTemplates),
                'rows' => $indexTemplates,
                'page' => 1,
                'size' => count($indexTemplates),
            ]),
        ]);
    }

    private function sortByName($a, $b) {
        return $b['name'] < $a['name'];
    }

    /**
     * @Route("/index-templates/create", name="index_templates_create")
     */
    public function create(Request $request): Response
    {
        $template = false;

        if ($request->query->get('template')) {
            $callRequest = new CallRequestModel();
            $callRequest->setPath('/_index_template/'.$request->query->get('template'));
            $callResponse = $this->callManager->call($callRequest);

            if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
                throw new NotFoundHttpException();
            }

            $template = $callResponse->getContent();
            $template = $template['index_templates'][0];
            $template['name'] = $template['name'].'-copy';
            $template['is_system'] = '.' == substr($template['name'], 0, 1);

            if (true == $template['is_system']) {
                throw new AccessDeniedHttpException();
            }
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_component_template');
        $callResponse = $this->callManager->call($callRequest);
        $results = $callResponse->getContent();
        $results = $results['component_templates'];

        $componentTemplates = [];
        foreach ($results as $row) {
            $componentTemplates[] = $row['name'];
        }

        $templateModel = new ElasticsearchIndexTemplateModel();
        if ($template) {
            $templateModel->convert($template);
        }
        $form = $this->createForm(CreateIndexTemplateType::class, $templateModel, ['component_templates' => $componentTemplates]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $json = $templateModel->getJson();
                $callRequest = new CallRequestModel();
                $callRequest->setMethod('PUT');
                $callRequest->setPath('/_index_template/'.$templateModel->getName());
                $callRequest->setJson($json);
                $callResponse = $this->callManager->call($callRequest);

                $this->addFlash('info', json_encode($callResponse->getContent()));

                return $this->redirectToRoute('index_templates_read', ['name' => $templateModel->getName()]);
            } catch (CallException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->renderAbstract($request, 'Modules/index_template/index_template_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/index-templates/{name}", name="index_templates_read")
     */
    public function read(Request $request, string $name): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_index_template/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
            throw new NotFoundHttpException();
        }

        $template = $callResponse->getContent();
        $template = $template['index_templates'][0];
        $template['is_system'] = '.' == substr($template['name'], 0, 1);

        return $this->renderAbstract($request, 'Modules/index_template/index_template_read.html.twig', [
            'template' => $template,
        ]);
    }

    /**
     * @Route("/index-templates/{name}/settings", name="index_templates_read_settings")
     */
    public function settings(Request $request, string $name): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_index_template/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
            throw new NotFoundHttpException();
        }

        $template = $callResponse->getContent();
        $template = $template['index_templates'][0];
        $template['is_system'] = '.' == substr($template['name'], 0, 1);

        return $this->renderAbstract($request, 'Modules/index_template/index_template_read_settings.html.twig', [
            'template' => $template,
        ]);
    }

    /**
     * @Route("/index-templates/{name}/mappings", name="index_templates_read_mappings")
     */
    public function mappings(Request $request, string $name): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_index_template/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
            throw new NotFoundHttpException();
        }

        $template = $callResponse->getContent();
        $template = $template['index_templates'][0];
        $template['is_system'] = '.' == substr($template['name'], 0, 1);

        return $this->renderAbstract($request, 'Modules/index_template/index_template_read_mappings.html.twig', [
            'template' => $template,
        ]);
    }

    /**
     * @Route("/index-templates/{name}/update", name="index_templates_update")
     */
    public function update(Request $request, string $name): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_index_template/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
            throw new NotFoundHttpException();
        }

        $template = $callResponse->getContent();
        $template = $template['index_templates'][0];
        $template['is_system'] = '.' == substr($template['name'], 0, 1);

        if (true == $template['is_system']) {
            throw new AccessDeniedHttpException();
        }

        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_component_template');
        $callResponse = $this->callManager->call($callRequest);
        $results = $callResponse->getContent();
        $results = $results['component_templates'];

        $componentTemplates = [];
        foreach ($results as $row) {
            $componentTemplates[] = $row['name'];
        }

        $templateModel = new ElasticsearchIndexTemplateModel();
        $templateModel->convert($template);
        $form = $this->createForm(CreateIndexTemplateType::class, $templateModel, ['component_templates' => $componentTemplates, 'update' => true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $json = $templateModel->getJson();
                $callRequest = new CallRequestModel();
                $callRequest->setMethod('PUT');
                $callRequest->setPath('/_index_template/'.$templateModel->getName());
                $callRequest->setJson($json);
                $callResponse = $this->callManager->call($callRequest);

                $this->addFlash('info', json_encode($callResponse->getContent()));

                return $this->redirectToRoute('index_templates_read', ['name' => $templateModel->getName()]);
            } catch (CallException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->renderAbstract($request, 'Modules/index_template/index_template_update.html.twig', [
            'template' => $template,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/index-templates/{name}/delete", name="index_templates_delete")
     */
    public function delete(Request $request, string $name): Response
    {
        $callRequest = new CallRequestModel();
        $callRequest->setPath('/_index_template/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        if (Response::HTTP_NOT_FOUND == $callResponse->getCode()) {
            throw new NotFoundHttpException();
        }

        $template = $callResponse->getContent();
        $template = $template['index_templates'][0];
        $template['is_system'] = '.' == substr($template['name'], 0, 1);

        if (true == $template['is_system']) {
            throw new AccessDeniedHttpException();
        }

        $callRequest = new CallRequestModel();
        $callRequest->setMethod('DELETE');
        $callRequest->setPath('/_index_template/'.$name);
        $callResponse = $this->callManager->call($callRequest);

        $this->addFlash('info', json_encode($callResponse->getContent()));

        return $this->redirectToRoute('index_templates');
    }
}
