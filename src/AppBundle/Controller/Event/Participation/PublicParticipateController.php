<?php
namespace AppBundle\Controller\Event\Participation;

use AppBundle\Entity\Participation;
use AppBundle\Form\ParticipationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicParticipateController extends Controller
{

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate", requirements={"eid": "\d+"}, name="event_public_participate")
     */
    public function participateAction(Request $request)
    {
        $eid = $request->get('eid');

        $repository = $this->getDoctrine()
                           ->getRepository('AppBundle:Event');

        $event = $repository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );

        }
        if (!$event->isActive()) {
            $this->addFlash(
                'danger',
                'Bei der gewählte Veranstaltung werden im Moment keine Anmeldungen erfasst'
            );

            return $this->redirectToRoute('homepage', array('eid' => $eid));
        }

        if ($request->getSession()
                    ->has('participation-' . $eid)
        ) {
            /** @var Participation $participation */
            $participation = $request->getSession()
                                     ->get('participation-' . $eid);
            $sessionEvent  = $participation->getEvent();
            if ($sessionEvent->getEid() == $eid) {
                $event = $sessionEvent;
            } else {
                return $this->render(
                    'event/public/miss.html.twig', array('eid' => $eid),
                    new Response(null, Response::HTTP_NOT_FOUND)
                );

            }
        } else {
            $participation = new Participation();

            /** @var \AppBundle\Entity\User $user */
            $user = $this->getUser();
            if ($user) {
                $participation->setNameLast($user->getNameLast());
                $participation->setNameFirst($user->getNameFirst());
            }
            $participation->setEvent($event);
        }

        $form = $this->createForm(ParticipationType::class, $participation);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()
                    ->set('participation-' . $eid, $participation);

            return $this->redirectToRoute('event_public_participate_confirm', array('eid' => $eid));
        }

        $user           = $this->getUser();
        $participations = array();
        if ($user) {
            $participations = $user->getAssignedParticipations();
        }

        return $this->render(
            'event/participation/public/begin.html.twig',
            array(
                'event'                          => $event,
                'acquisitionFieldsParticipation' => $event->getAcquisitionAttributes(true, false),
                'participations'                 => $participations,
                'acquisitionFieldsParticipant'   => $event->getAcquisitionAttributes(false, true),
                'form'                           => $form->createView()
            )
        );
    }

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate/prefill/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_public_participate_prefill")
     */
    public function participatePrefillAction($eid, $pid, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Um Daten einer früherer Anmeldung verwenden zu können, müssen Sie angemeldet sein. Sie können sich jetzt <a href="%s">anmelden</a>, oder die Daten hier direkt eingeben.',
                    $this->generateUrl('fos_user_security_login')
                )
            );
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        $em                      = $this->getDoctrine()->getManager();
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participationPrevious   = $participationRepository->findOneBy(
            array('pid' => $pid, 'assignedUser' => $user->getUid())
        );
        if (!$participationPrevious) {
            $this->addFlash(
                'danger',
                'Es konnte keine passende Anmeldung von Ihnen gefunden werden, mit der das Anmeldeformular hätte vorausgefüllt werden können.'
            );
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(array('eid' => $eid));

        $participation = Participation::createFromTemplateForEvent($participationPrevious, $event);
        $participation->setAssignedUser($user);

        $request->getSession()->set('participation-' . $eid, $participation);
        $this->addFlash(
            'success',
            'Die Anmeldung wurde mit Daten einer früheren Anmeldung vorausgefüllt. Bitte überprüfen Sie sorgfältig ob die Daten noch richtig sind.'
        );
        return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
    }

    /**
     * Page for list of events
     *
     * @Route("/event/{eid}/participate/confirm", requirements={"eid": "\d+"}, name="event_public_participate_confirm")
     */
    public function confirmParticipationAction($eid, Request $request)
    {
        if (!$request->getSession()
                     ->has('participation-' . $eid)
        ) {
            return $this->redirectToRoute('event_public_participate', array('eid' => $eid));
        }

        /** @var Participation $participation */
        $participation = $request->getSession()
                                 ->get('participation-' . $eid);
        $event         = $participation->getEvent();

        if (!$participation instanceof Participation
            || $eid != $participation->getEvent()
                                     ->getEid()
        ) {
            throw new BadRequestHttpException('Given participation data is invalid');
        }

        if ($request->query->has('confirm')) {
            $em = $this->getDoctrine()
                       ->getManager();
            if ($event->getIsAutoConfirm()) {
                $participation->setIsConfirmed(true);
            }
            $managedParticipation = $em->merge($participation);

            $em->persist($managedParticipation);
            $em->flush();

            $participationManager = $this->get('app.participation_manager');
            $participationManager->mailParticipationRequested($participation, $event);

            $request->getSession()
                    ->remove('participation-' . $eid);

            if ($request->getSession()
                        ->has('participationList')
            ) {
                $participationList = $request->getSession()
                                             ->get('participationList');
            } else {
                $participationList = array();
            }
            $participationList[] = $managedParticipation->getPid();
            $request->getSession()
                    ->set('participationList', $participationList);

            $message
                = '<p>Wir haben Ihren Teilnahmewunsch festgehalten. Sie erhalten eine automatische Bestätigung, dass die Anfrage bei uns eingegangen ist.</p>';

            if (!$this->getUser()) {
                $message .= sprintf(
                    '<p>Sie können sich jetzt <a href="%s">registrieren</a>. Dadurch können Sie Korrekturen an den Anmeldungen vornehmen oder zukünftige Anmeldungen schneller ausfüllen.</p>',
                    $this->container->get('router')->generate('fos_user_registration_register')
                );
            }
            $repositoryNewsletter = $this->getDoctrine()->getRepository('AppBundle:NewsletterSubscription');
            if (!$repositoryNewsletter->findOneByEmail($participation->getEmail())) {
                $message .= sprintf(
                    '<p>Sie können jetzt den <a href="%s">Newsletter abonnieren</a>, um auch in Zukunft von unseren Aktionen erfahren.</p>',
                    $this->container->get('router')->generate('newsletter_subscription')
                );
            }

            $this->addFlash(
                'success',
                $message
            );

            return $this->redirectToRoute('event_public_detail', array('eid' => $eid));
        } else {
            return $this->render(
                'event/participation/public/confirm.html.twig', array(
                                                                  'participation' => $participation,
                                                                  'event'         => $event
                                                              )
            );
        }
    }
}