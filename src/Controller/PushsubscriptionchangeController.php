<?php

namespace App\Controller;

use App\Controller\AbstractAppController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class PushsubscriptionchangeController extends AbstractAppController
{
    /**
     * @Route("/pushsubscriptionchange", name="pushsubscriptionchange")
     */
    public function index(Request $request, LoggerInterface $logger): JsonResponse
    {

        $content = $request->getContent();
        if ($content) {
            $content = json_decode($content, true);

            $logger->info('pushsubscriptionchange', $content);
        } else {
            $logger->info('pushsubscriptionchange');
        }

        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}
