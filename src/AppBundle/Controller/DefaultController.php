<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Flash;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

        $activeCount = 0;
        /** @var Event $event */
        foreach ($eventList as $event) {
            if ($event->isActive()) {
                ++$activeCount;
            }
        }

        $customization = $this->get('app.twig_global_customization');
        $description = sprintf('Überblick über alle Veranstaltungen von %s. ', $customization->organizationName());
        switch ($activeCount ) {
            case 0:
                $description .= 'Anmeldungen können hier abgegeben werden.';
                break;
            case 1:
                $description .= 'Anmeldungen können derzeit für eine Veranstaltung abgegeben werden.';
                break;
            default:
                $description .= 'Anmeldungen können derzeit für '.$activeCount.' Veranstaltungen abgegeben werden.';
                break;
        }

        return $this->render(
            'default/index.html.twig',
            [
                'events'          => $eventList,
                'pageDescription' => $description,
                'participations'  => $participations
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
     * @Route("/robots.txt")
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
    public function redirectToHomeAction()
    {
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/crossdomain.xml")
     * @Route("/clientaccesspolicy.xml")
     * @Route("/.well-known/assetlinks.json")
     */
    public function unsupportedAction()
    {
        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @Route("/contribute.json")
     */
    public function contributeAction()
    {
        return new JsonResponse(
            [
                'name'        => 'Juvem',
                'description' => 'Juvem is a symfony based web application to manage events and newsletters',
                'repository'  => [
                    'url'     => 'https://github.com/theoboldt/juvem.git',
                    'license' => 'MIT'
                ],
                'participate' => [
                    'home' => 'https://github.com/theoboldt/juvem',
                    'irc' => '',
                    'irc-contacts' => []
                ],
                'keywords'    => [
                    'PHP',
                    'Symfony',
                    'Twitter Bootstrap',
                    'jQuery'
                ],
                'bugs'        => [
                    'list'   => 'https://github.com/theoboldt/juvem/issues',
                    'report' => 'https://github.com/theoboldt/juvem/issues/new'
                ],
                'urls'        => [
                    'prod' => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]
        );
    }
}
