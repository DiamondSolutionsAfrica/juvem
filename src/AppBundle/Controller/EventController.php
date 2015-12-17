<?php
namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class EventController extends Controller
{

    /**
     * @Route("/event/list", name="event_list")
     */
    public function listAction(Request $request)
    {
        return $this->render('event/list.html.twig');
    }

    /**
     * @Route("/event/list.json")
     */
    public function listDataAction(Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:Event');
/*
        $criteria   = array();
        $eventEntityList    = $repository->findBy(
            $criteria, array('title' => $request->get('order')), $request->get('limit'), $request->get('offset')
        );
// using client side paginations and filtering
*/
        $eventEntityList    = $repository->findAll();

        $dateFormatDay = 'd.m.y';
        $dateFormatDayHour = 'd.m.y H:i';
        $glyphicon  = '<span class="glyphicon glyphicon-%s" aria-hidden="true"></span> ';

        $eventList  = array();
        /** @var Event $event */
        foreach($eventEntityList as $event) {
            $eventStatus    = '';
            if ($event->isVisible()) {
                $eventStatus    .= sprintf($glyphicon, 'eye-open');
            } else {
                $eventStatus    .= sprintf($glyphicon, 'eye-close');
            }

            if ($event->isActive()) {
                $eventStatus    .= sprintf($glyphicon, 'folder-open');
            } else {
                $eventStatus    .= sprintf($glyphicon, 'folder-close');
            }

            $eventStartFormat   = $dateFormatDayHour;
            if ($event->getStartDate()->format('Hi') == '0000') {
                $eventStartFormat = $dateFormatDay;
            }
            $eventEndFormat   = $dateFormatDayHour;
            if ($event->getEndDate()->format('Hi') == '0000') {
                $eventEndFormat = $dateFormatDay;
            }

            $eventList[]    = array(
                'eid' => $event->getEid(),
                'title' => $event->getTitle(),
                'description' => $event->getTitle(),
                'start_date' => $event->getStartDate()->format($eventStartFormat),
                'end_date' => $event->getEndDate()->format($eventEndFormat),
                'status' => $eventStatus
            );
        }


        return new JsonResponse($eventList);
    }

    /**
     * @Route("/event/new", name="event_new")
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));
        
        $form = $this->createFormBuilder($event)
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add('description', TextareaType::class, array('label' => 'Beschreibung'))
            ->add('startDate', DateTimeType::class, array('label' => 'Beginn'))
            ->add('endDate', DateTimeType::class, array('label' => 'Ende'))
            ->add('isActive', ChoiceType::class, array(
                    'label' => 'Status',
                    'choices' => array('Für Anmeldungen offen' => true, 'Keine Anmeldungen möglich' => false),
                    'choices_as_values' => true, 'expanded' => true
            ))
            ->add('isVisible', ChoiceType::class, array(
                'label' => 'Sichtbarkeit', 'choices' => array('Aktiv' => true, 'Versteckt' => false),
                'choices_as_values' => true, 'expanded' => true
            ))
            ->add('save', SubmitType::class, array('label' => 'Veranstaltung erstellen'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            dump($form->getData());
            $em = $this->getDoctrine()->getManager();

            $em->persist($event);
            $em->flush();

            return $this->redirect('/event/list');
        }

        return $this->render('event/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

}