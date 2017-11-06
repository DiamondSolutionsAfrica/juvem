<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Participation;

use AppBundle\BitMask\LabelFormatter;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Participant;
use AppBundle\Entity\PhoneNumber;
use AppBundle\Form\ParticipantType;
use AppBundle\Form\ParticipationBaseType;
use AppBundle\Form\ParticipationPhoneNumberList;
use AppBundle\InvalidTokenHttpException;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminSingleController extends Controller
{
    /**
     * Details of one participation including all participants
     *
     * @Route("/admin/event/{eid}/participation/{pid}", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                  name="event_participation_detail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participationDetailAction(Request $request)
    {
        $participationRepository = $this->getDoctrine()->getRepository('AppBundle:Participation');

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
                case 'reject':
                    $participation->setIsRejected(true);
                    break;
                case 'rereject':
                    $participation->setIsRejected(false);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown action transmitted');
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($participation);
            $em->flush();
        }

        $statusFormatter = ParticipantStatus::formatter();
        $foodFormatter   = new LabelFormatter();

        $phoneNumberList = array();
        /** @var PhoneNumber $phoneNumberEntity */
        foreach ($participation->getPhoneNumbers()->getIterator() as $phoneNumberEntity) {
            /** @var \libphonenumber\PhoneNumber $phoneNumber */
            $phoneNumber       = $phoneNumberEntity->getNumber();
            $phoneNumberList[] = $phoneNumber;
        }
        $commentManager = $this->container->get('app.comment_manager');

        $similarParticipants = [];
        /** @var Participant $participant */
        foreach ($participation->getParticipants() as $participant) {
            $similarParticipants[$participant->getAid()] = $participationRepository->relatedParticipants($participant);
        }

        return $this->render(
            'event/participation/admin/detail.html.twig',
            [
                'commentManager'      => $commentManager,
                'event'               => $event,
                'participation'       => $participation,
                'similarParticipants' => $similarParticipants,
                'foodFormatter'       => $foodFormatter,
                'statusFormatter'     => $statusFormatter,
                'phoneNumberList'     => $phoneNumberList,
                'form'                => $form->createView()
            ]
        );
    }

    /**
     * Detail page for one single event
     *
     * @Route("/admin/event/participation/confirm", name="event_participation_confirm_mail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function participationConfirmAction(Request $request)
    {
        $token = $request->get('_token');
        $pid   = $request->get('pid');

        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken($pid)) {
            throw new InvalidTokenHttpException();
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
     * Page edit an participation
     *
     * @Route("/admin/event/{eid}/participation/{pid}/edit", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                       name="admin_edit_participation")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editParticipationAction(Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));
        $event         = $participation->getEvent();

        $form = $this->createForm(ParticipationBaseType::class, $participation);
        $form->remove('phoneNumbers');
        $form->remove('participants');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em                   = $this->getDoctrine()->getManager();
            $managedParticipation = $em->merge($participation);
            $em->persist($managedParticipation);
            $em->flush();
            $this->addFlash(
                'success',
                'Die Änderungen wurden gespeichert.'
            );
            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $event->getEid(), 'pid' => $participation->getPid())
            );
        }
        return $this->render(
            'event/participation/edit-participation.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false),
            )
        );
    }

    /**
     * Page edit an participation
     *
     * @Route("/admin/event/{eid}/participation/{pid}/phone", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                        name="admin_edit_phonenumbers")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editPhoneNumbersAction(Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $request->get('pid')));
        $event         = $participation->getEvent();

        $originalPhoneNumbers = new ArrayCollection();
        foreach ($participation->getPhoneNumbers() as $number) {
            $originalPhoneNumbers->add($number);
        }

        $form = $this->createForm(ParticipationPhoneNumberList::class, $participation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($originalPhoneNumbers as $number) {
                if (false === $participation->getPhoneNumbers()->contains($number)) {
                    $participation->getPhoneNumbers()->removeElement($number);
                    $em->remove($number);
                }
            }
            /** @var PhoneNumber $number */
            foreach ($participation->getPhoneNumbers() as $number) {
                $number->setParticipation($participation);
            }

            $em->persist($participation);
            $em->flush();

            $this->addFlash(
                'success',
                'Die Änderungen wurden gespeichert.'
            );
            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $event->getEid(), 'pid' => $participation->getPid())
            );
        }
        return $this->render(
            'event/participation/edit-phone-numbers.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(true, false),
            )
        );
    }

    /**
     * Page edit an participant
     *
     * @Route("/admin/event/{eid}/participation/{pid}/participant/{aid}", requirements={"eid": "\d+", "pid": "\d+",
     *                                                                    "aid": "\d+"}, name="admin_edit_participant")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editParticipantAction($eid, $pid, $aid, Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository('AppBundle:Participant');
        $participant   = $repository->findOneBy(array('aid' => $aid));
        $participation = $participant->getParticipation();
        $event         = $participation->getEvent();

        $form = $this->createForm(
            ParticipantType::class, $participant, array('participation' => $participation)
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($participant);
            $em->flush();

            if ($participant->getGender() == Participant::TYPE_GENDER_FEMALE) {
                $message = 'dem Teilnehmerin ';
            } else {
                $message = 'der Teilnehmer ';
            }
            $this->addFlash(
                'success',
                'Die Änderungen an '.$message.$participant->getNameFirst().' wurden gespeichert.'
            );

            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $eid, 'pid' => $pid)
            );
        }
        return $this->render(
            'event/participation/edit-participant.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'participant'       => $participant,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, true),
            )
        );
    }

    /**
     * Page add a participant
     *
     * @Route("/admin/event/{eid}/participation/{pid}/participant/add", requirements={"eid": "\d+", "pid": "\d+"},
     *                                                                  name="admin_add_participant")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function addParticipantAction($eid, $pid, Request $request)
    {
        $repository    = $this->getDoctrine()->getRepository('AppBundle:Participation');
        $participation = $repository->findOneBy(array('pid' => $pid));
        $participant   = new Participant();
        $participant->setParticipation($participation);
        $event = $participation->getEvent();

        $form = $this->createForm(
            ParticipantType::class, $participant, array('participation' => $participation)
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($participant);
            $em->flush();

            if ($participant->getGender() == Participant::TYPE_GENDER_FEMALE) {
                $message = 'Der Teilnehmerin ';
            } else {
                $message = 'Die Teilnehmer ';
            }
            $this->addFlash(
                'success',
                $message.' '.$participant->getNameFirst().' wurde hinzugefügt.'
            );
            return $this->redirectToRoute(
                'event_participation_detail', array('eid' => $eid, 'pid' => $pid)
            );
        }
        return $this->render(
            'event/participation/add-participant.html.twig',
            array(
                'adminView'         => true,
                'form'              => $form->createView(),
                'participation'     => $participation,
                'participant'       => $participant,
                'event'             => $event,
                'acquisitionFields' => $event->getAcquisitionAttributes(false, true),
            )
        );
    }
}
