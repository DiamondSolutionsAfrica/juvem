<?php

namespace AppBundle\Controller\Event\Participation;

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\AcquisitionAttributeFillout;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Export\CustomizedExportConfiguration;
use AppBundle\Export\ParticipantsBirthdayAddressExport;
use AppBundle\Export\ParticipantsExport;
use AppBundle\Export\ParticipantsMailExport;
use AppBundle\Export\ParticipationsExport;
use AppBundle\Twig\Extension\BootstrapGlyph;
use libphonenumber\PhoneNumberUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminMultipleController extends Controller
{
    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants", requirements={"eid": "\d+"}, name="event_participants_list")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(array('eid' => $eid));
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', array('eid' => $eid),
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        return $this->render('event/participation/admin/participants-list.html.twig', array('event' => $event));
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/admin/event/{eid}/participants.json", requirements={"eid": "\d+"}, name="event_participants_list_data")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
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

        $participantEntityList = $eventRepository->participantsList($event, null, true, true);

        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $statusFormatter = ParticipantStatus::formatter();

        $participantList = array();
        /** @var Participant $participant */
        foreach ($participantEntityList as $participant) {
            $participation        = $participant->getParticipation();
            $participationDate    = $participation->getCreatedAt();
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

            $age = number_format($participant->getAgeAtEvent(), 1, ',', '.');
            if ($participant->hasBirthdayAtEvent()) {
                $glyph = new BootstrapGlyph();
                $age .= ' ' . $glyph->bootstrapGlyph('gift');
            }
            $participantStatusText = $statusFormatter->formatMask($participantStatus);
            if ($participant->getDeletedAt()) {
                $participantStatusText .= ' <span class="label label-danger">gelöscht</span>';
            }

            $participantEntry = array(
                'aid'              => $participant->getAid(),
                'pid'              => $participant->getParticipation()->getPid(),
                'is_deleted'       => (int)($participant->getDeletedAt() instanceof \DateTime),
                'is_paid'          => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_PAID),
                'is_withdrawn'     => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN),
                'is_confirmed'     => (int)$participantStatus->has(ParticipantStatus::TYPE_STATUS_CONFIRMED),
                'nameFirst'        => $participant->getNameFirst(),
                'nameLast'         => $participant->getNameLast(),
                'age'              => $age,
                'phone'            => implode(', ', $participantPhoneList),
                'status'           => $participantStatusText,
                'gender'           => $participant->getGender(true),
                'registrationDate' => $participationDate->format(Event::DATE_FORMAT_DATE_TIME),
                'action'           => $participantAction
            );
            /** @var AcquisitionAttributeFillout $fillout */
            foreach ($participation->getAcquisitionAttributeFillouts() as $fillout) {
                $participantEntry['participation_acq_field_' . $fillout->getAttribute()->getBid()]
                    = $fillout->__toString();
            }
            foreach ($participant->getAcquisitionAttributeFillouts() as $fillout) {
                $participantEntry['participant_acq_field_' . $fillout->getAttribute()->getBid()] = $fillout->__toString(
                );
            }

            $participantList[] = $participantEntry;
        }

        return new JsonResponse($participantList);
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants/export", requirements={"eid": "\d+"}, name="event_participants_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipantsAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        $participantList = $eventRepository->participantsList($event);

        $export = new ParticipantsExport(
            $this->get('app.twig_global_customization'), $event, $participantList, $this->getUser()
        );
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Teilnehmer.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }


    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participations/export", requirements={"eid": "\d+"},
     *                                                    name="event_participations_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipationsAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        $participationsList = $eventRepository->participationsList($event);

        $export = new ParticipationsExport(
            $this->get('app.twig_global_customization'), $event, $participationsList, $this->getUser()
        );
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Anmeldungen.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants/birthday_address_export", requirements={"eid": "\d+"},
     *                                                    name="event_participants_birthday_address_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipantsBirthdayAddressAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }
        $participantList = $eventRepository->participantsList($event);

        $export = new ParticipantsBirthdayAddressExport($this->get('app.twig_global_customization'), $event, $participantList, $this->getUser());
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Teilnehmer.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }

    /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/participants_mail/export", requirements={"eid": "\d+"},
     *                                                    name="event_participants_mail_export")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportParticipantsMailAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $event           = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $participantList    = $eventRepository->participantsList($event);
        $participationsList = $eventRepository->participationsList($event);

        $export = new ParticipantsMailExport(
            $this->get('app.twig_global_customization'), $event, $participantList, $participationsList, $this->getUser()
        );
        $export->setMetadata();
        $export->process();

        $response = new StreamedResponse(
            function () use ($export) {
                $export->write('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $event->getTitle() . ' - Anmeldungen.xlsx'
        );
        $response->headers->set('Content-Disposition', $d);

        return $response;
    }

     /**
     * Page for list of participants of an event
     *
     * @Route("/admin/event/{eid}/export", requirements={"eid": "\d+"}, name="event_export_generator")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function exportGeneratorAction($eid)
    {
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');

        $event = $eventRepository->findOneBy(['eid' => $eid]);
        if (!$event) {
            return $this->render(
                'event/public/miss.html.twig', ['eid' => $eid],
                new Response(null, Response::HTTP_NOT_FOUND)
            );
        }

        $config = ['export' => ['participant' => ['firstName' => true, 'lastName' => false], 'participation' => []]];

        $processor     = new Processor();
        $configuration = new CustomizedExportConfiguration($event);
        $tree          = $configuration->getConfigTreeBuilder()->buildTree();


        $processedConfiguration = $processor->processConfiguration($configuration, $config);

        return $this->render('event/admin/export-generator.html.twig', array('event' => $event, 'config' => $tree->getChildren()));
    }

}
