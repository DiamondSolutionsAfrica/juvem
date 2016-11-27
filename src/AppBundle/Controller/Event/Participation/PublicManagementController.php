<?php
namespace AppBundle\Controller\Event\Participation;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Event;
use AppBundle\Entity\Participant;
use AppBundle\Entity\Participation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PublicManagementController extends Controller
{
    /**
     * Page for list of events
     *
     * @Route("/participation", name="public_participations")
     * @Security("has_role('ROLE_USER')")
     */
    public function listParticipationsAction()
    {
        return $this->render('event/participation/public/participations-list.twig');
    }

    /**
     * Data provider for events participants list grid
     *
     * @Route("/participations.json", name="public_participations_list_data")
     * @Security("has_role('ROLE_USER')")
     */
    public function listParticipantsDataAction(Request $request)
    {
        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $dateFormatDay     = 'd.m.y';
        $dateFormatDayHour = 'd.m.y H:i';

        $participationRepository = $this->getDoctrine()
                                        ->getRepository('AppBundle:Participation');

        $user = $this->getUser();

        $participationList = $participationRepository->findBy(array('assignedUser' => $user->getUid()));

        $participationListResult = array();
        /** @var Participant $participant */
        foreach ($participationList as $participation) {
            $event = $participation->getEvent();


            $eventStartDate = $event->getStartDate()->format(Event::DATE_FORMAT_DATE);
            if ($event->hasEndDate()) {
                $eventEndDate = $event->getEndDate()->format(Event::DATE_FORMAT_DATE);
            } else {
                $eventEndDate = $eventStartDate;
            }
            if ($event->hasStartTime()) {
                $eventStartDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }
            if ($event->hasEndTime()) {
                $eventEndDate .= ' ' . $event->getEndTime()->format(Event::DATE_FORMAT_TIME);
            } elseif ($event->hasStartTime()) {
                $eventEndDate .= ' ' . $event->getStartTime()->format(Event::DATE_FORMAT_TIME);
            }

            $eventTime = sprintf(
                '%s - %s',
                $eventStartDate,
                $eventEndDate
            );

            $participantsString = '';
            foreach ($participation->getParticipants() as $participant) {
                $participantsString .= sprintf(
                    ' %s %s', $participant->getNameFirst(), $statusFormatter->formatMask($participant->getStatus(true))
                );
            }

            $participationListResult[] = array(
                'pid'          => $participation->getPid(),
                'eventTitle'   => $event->getTitle(),
                'eventTime'    => $eventTime,
                'participants' => $participantsString
            );
        }

        return new JsonResponse($participationListResult);
    }


    /**
     * Page for list of events
     *
     * @Route("/participation/{pid}", requirements={"pid": "\d+"}, name="public_participation_detail")
     * @Security("has_role('ROLE_USER')")
     */
    public function participationDetailedAction(Request $request)
    {

        $statusFormatter = new LabelFormatter();
        $statusFormatter->addAbsenceLabel(
            ParticipantStatus::TYPE_STATUS_CONFIRMED, ParticipantStatus::LABEL_STATUS_UNCONFIRMED
        );

        $user          = $this->getUser();
        $repository    = $this->getDoctrine()
                              ->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));

        if ($participation->getAssignedUser()
                          ->getUid() != $user->getUid()
        ) {
            throw new AccessDeniedHttpException('Participation is related to another user');
        }

        $form = $this->createFormBuilder()
                     ->add('aid', HiddenType::class)
                     ->add('action', HiddenType::class)
                     ->add('value', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action   = $form->get('action')->getData();
            $aid      = $form->get('aid')->getData();
            $newValue = !$form->get('value')->getData();

            /** @var Participant $participationParticipant */
            foreach ($participation->getParticipants() as $participationParticipant) {
                if ($participationParticipant->getAid() == $aid) {
                    $participant = $participationParticipant;
                }
            }
            if (isset($participant)) {
                switch ($action) {
                    case 'withdraw':
                        if ($participant->isWithdrawn()) {
                            if ($newValue) {
                                $this->addFlash(
                                    'success',
                                    'Die Anmeldung wurde von uns bereits als zurückgezogen markiert. Es ist keine weitere Aktion nötig.'
                                );
                            } else {
                                $this->addFlash(
                                    'danger',
                                    'Die Anmeldung wurde von uns bereits als zurückgezogen markiert. Wenn Sie die Anmeldung diesen Teilnehmers reaktivieren möchten, wenden Sie sich in diesem Fall bitte direkt an das Jugendwerk.'
                                );
                            }
                        } else {
                            $participant->setIsWithdrawRequested($newValue);
                            $em                   = $this->getDoctrine()->getManager();
                            $managedParticipation = $em->merge($participation);

                            $em->persist($managedParticipation);
                            $em->flush();
                            if ($newValue) {
                                $this->addFlash(
                                    'success',
                                    'Ihre Anfrage zur Zurücknahme dieser Anmeldung wurde registiert und wird demnächst von uns bearbeitet. Wenn sich der Status der Anmeldung des betroffenen Teilnehmers nicht innerhalb einiger Tage ändert, wenden Sie sich bitte direkt an das Jugendwerk.'
                                );
                            } else {
                                $this->addFlash(
                                    'success',
                                    'Sie haben ihre Anfrage auf Zurücknahme dieser Anmeldung entfernt. Die Anmeldung ist damit wieder gültig.'
                                );
                            }
                        }
                        break;

                }

                return $this->redirectToRoute('public_participation_detail', array('pid' => $participation->getPid()));
            }

        }

        return $this->render(
            'event/participation/public/detail.html.twig', array(
                                                             'form'            => $form->createView(),
                                                             'participation'   => $participation,
                                                             'event'           => $participation->getEvent(),
                                                             'statusFormatter' => $statusFormatter
                                                         )
        );

    }
}