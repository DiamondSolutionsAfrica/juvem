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

use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Form\AcquisitionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AcquisitionController extends Controller
{
    /**
     * @Route("/admin/acquisition/list", name="acquisition_list")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listAction(Request $request)
    {
        return $this->render('acquisition/list.html.twig');
    }


    /**
     * Data provider for event list grid
     *
     * @Route("/admin/acquisition/list.json", name="acquisition_list_data")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function listDataAction(Request $request)
    {
        $repository          = $this->getDoctrine()
                                    ->getRepository('AppBundle:AcquisitionAttribute');
        $attributeEntityList = $repository->findBy(array('deletedAt' => null));

        $attributeList = array();
        /** @var AcquisitionAttribute $attribute */
        foreach ($attributeEntityList as $attribute) {

            $attributeList[] = array(
                'bid'                    => $attribute->getBid(),
                'management_title'       => $attribute->getManagementTitle(),
                'management_description' => $attribute->getManagementDescription(),
                'type'                   => $attribute->getFieldType(true),
                'form_title'             => $attribute->getFormTitle(),
                'form_description'       => $attribute->getFormDescription()
            );
        }


        return new JsonResponse($attributeList);
    }

    /**
     * Detail page for a acquisition attribute
     *
     * @ParamConverter("attribute", class="AppBundle:AcquisitionAttribute", options={"id" = "bid"})
     * @Route("/admin/acquisition/{bid}", requirements={"bid": "\d+"}, name="acquisition_detail")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function detailEventAction(Request $request, AcquisitionAttribute $attribute)
    {
        $form = $this->createFormBuilder()
                     ->add('action', HiddenType::class)
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $form->get('action')
                           ->getData();
            switch ($action) {
                case 'delete':
                    $attribute->setDeletedAt(new \DateTime());
                    break;
                case 'restore':
                    $attribute->setDeletedAt(null);
                    break;
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($attribute);
            $em->flush();
        }

        return $this->render(
            'acquisition/detail.html.twig',
            [
                'form'        => $form->createView(),
                'acquisition' => $attribute,
                'events'      => $attribute->getEvents()
            ]
        );
    }


    /**
     * Edit page for one single attribute
     *
     * @ParamConverter("attribute", class="AppBundle:AcquisitionAttribute", options={"id" = "bid"})
     * @Route("/admin/acquisition/{bid}/edit", requirements={"bid": "\d+"}, name="acquisition_edit")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function editAction(Request $request, AcquisitionAttribute $attribute)
    {
        $form = $this->createForm(AcquisitionType::class, $attribute);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($attribute);
            $em->flush();

            return $this->redirectToRoute('acquisition_detail', array('bid' => $attribute->getBid()));
        }

        return $this->render(
            'acquisition/edit.html.twig', array(
                                            'acquisition'       => $attribute,
                                            'showChoiceOptions' => ($attribute->getFieldType() == ChoiceType::class),
                                            'form'              => $form->createView(),
                                        )
        );
    }

    /**
     * Create a new acquisition attribute
     *
     * @Route("/admin/acquisition/new", name="acquisition_new")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function newAction(Request $request)
    {
        $attribute = new AcquisitionAttribute();

        $form = $this->createForm(AcquisitionType::class, $attribute);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()
                       ->getManager();

            $em->persist($attribute);
            $em->flush();

            return $this->redirectToRoute('acquisition_list');
        }

        return $this->render(
            'acquisition/new.html.twig', array(
                                           'form' => $form->createView(),
                                       )
        );
    }
}