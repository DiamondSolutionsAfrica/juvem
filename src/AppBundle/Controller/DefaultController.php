<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Flash;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $repositoryFlash = $this->getDoctrine()->getRepository('AppBundle:Flash');
        $flashList       = $repositoryFlash->findValid();
        /** @var Flash $flash */
        foreach ($flashList as $flash) {
            $this->addFlash(
                $flash->getType(),
                $this->container->get('markdown.parser')->transformMarkdown($flash->getMessage())
            );
        }

        $repositoryEvent = $this->getDoctrine()->getRepository('AppBundle:Event');
        $eventList       = $repositoryEvent->findAllWithCounts();

        $user           = $this->getUser();
        $participations = [];
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'default/index.html.twig',
            [
                'events'         => $eventList,
                'participations' => $participations
            ]
        );
    }

    /**
     * @Route("/heartbeat", name="heartbeat")
     */
    public function heartbeatAction()
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/css/all.css.map")
     * @Route("/js/all.js.map")
     * @Route("/css/all.min.css.map")
     * @Route("/js/all.min.js.map")
     */
    public function ressourceUnavailableAction()
    {
        return new Response(null, Response::HTTP_GONE);
    }

    /**
     * @Route("/apple-app-site-association")
     * @Route("/.well-known/apple-app-site-association")
     */
    public function appleUniversalLinksAction()
    {
        return new JsonResponse(['applinks' => ['apps' => [], 'details' => []]]);
    }

    /**
     * @Route("/m")
     * @Route("/mobile")
     */
    public function redirectToHomeAction() {
            return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/.well-known/assetlinks.json")
     */
    public function unsupportedAction()
    {
        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

}
