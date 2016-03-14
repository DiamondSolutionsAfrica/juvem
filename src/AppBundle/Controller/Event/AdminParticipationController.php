<?php

namespace AppBundle\Controller\Event;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\EventParticipationType;
use AppBundle\Form\EventType;
use AppBundle\Form\ModalActionType;

use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminParticipationController extends Controller
{
    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants", requirements={"eid": "\d+"}, name="event_participants_list")
     */
    public function listParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }


        $em                    = $this->getDoctrine()
                                      ->getManager();
        $query                 = $em->createQuery(
            'SELECT p
                   FROM AppBundle:Participation p
              WHERE p.event = :eid'
        )
                                    ->setParameter('eid', $eid);
        $participantEntityList = $query->getResult();

        return $this->render('event/participation/admin/participants-list.html.twig', array('event' => $event));
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $eid             = $request->get('eid');
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $em                    = $this->getDoctrine()
                                      ->getManager();
        $query                 = $em->createQuery(
            'SELECT a
               FROM AppBundle:Participant a,
                    AppBundle:Participation p
              WHERE a.participation = p.pid
                AND p.event = :eid'
        )
                                    ->setParameter('eid', $eid);
        $participantEntityList = $query->getResult();

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $participantList = array();
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            $participation        = $participant->getParticipation();
            $participantPhoneList = array();

            /** @var PhoneNumber $phoneNumberEntity */
            foreach ($participation->getPhoneNumbers()
                                   ->getIterator() as $phoneNumberEntity) {
                /** @var \libphonenumber\PhoneNumber $phoneNumber */
                $phoneNumber            = $phoneNumberEntity->getNumber();
                $participantPhoneList[] = $phoneNumberUtil->formatOutOfCountryCallingNumber($phoneNumber, 'DE');
            }

            $participantAction = '';

            $participantStatus = $participant->getStatus(true);

            $participantList[] = array(
                'aid'          => $participant->getAid(),
                'pid'          => $participant->getParticipation()
                                              ->getPid(),
                'is_deleted'   => (int)($participant->getDeletedAt() instanceof \DateTime),
                'is_paid'      => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_PAID),
                'is_withdrawn' => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN),
                'is_confirmed' => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'nameFirst'    => $participant->getNameFirst(),
                'nameLast'     => $participant->getNameLast(),
                'age'          => number_format($participant->getAgeAtEvent(), 1, ',', '.'),
                'phone'        => implode(', ', $participantPhoneList),
                'status'       => $statusFormatter->formatMask($participantStatus),
                'action'       => $participantAction
            );
        }

        return new JsonResponse($participantList);
    }


    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participation/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_participation_detail")
     */
    public function participationDetailAction(Request $request)
    {
        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }
        $event = $participation->getEvent();

        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')
                           ->getData();
            switch ($action) {
                case 'delete':
                    $participation->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $participation->setDeletedAt(null);
                    break;
                case 'withdraw':
                    $participation->setIsWithdrawn(true);
                    break;
                case 'reactivate':
                    $participation->setIsWithdrawn(false);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($participation);
            $em->flush();
        }

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );
        $foodFormatter = new LabelFormatter();

        $phoneNumberList = array();
        /** @var PhoneNumber $phoneNumberEntity */
        foreach ($participation->getPhoneNumbers()
                               ->getIterator() as $phoneNumberEntity) {
            /** @var \libphonenumber\PhoneNumber $phoneNumber */
            $phoneNumber       = $phoneNumberEntity->getNumber();
            $phoneNumberList[] = $phoneNumber;
        }

        return $this->render(
            'event/participation/admin/detail.html.twig',
            array('event'           => $event,
                  'participation'   => $participation,
                  'foodFormatter'   => $foodFormatter,
                  'statusFormatter' => $statusFormatter,
                  'phoneNumberList' => $phoneNumberList,
                  'form'            => $form->createView()
            )
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/participation/confirm", name="event_participation_confirm_mail")
     */
    public function participationConfirmAction(Request $request)
    {
        $token = $request->get('_token');
        $pid   = $request->get('pid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($pid)) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $participation = $participationRepository->findOneBy(array('pid' => $request->get('pid')));
        if (!$participation) {
            throw new BadRequestHttpException('Requested participation event not found');
        }

        $participationManager = $this->get('app.participation_manager');
        $participationManager->mailParticipationConfirmed($participation, $participation->getEvent());

        return new JsonResponse(
            array(
                'success' => true
            )
        );
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants/export", requirements={"eid": "\d+"}, name="event_participants_export")
     */
    public function exportParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()
                                ->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        return new JsonResponse(array('url' => 'null'));
    }
}
