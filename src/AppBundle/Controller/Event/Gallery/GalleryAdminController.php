<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Event\Gallery;


use AppBundle\Entity\Event;
use AppBundle\Entity\GalleryImage;
use AppBundle\InvalidTokenHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class GalleryAdminController extends BaseGalleryController
{

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/gallery", requirements={"eid": "\d+"}, name="event_gallery_admin")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function detailsAction(Event $event)
    {
        $repository  = $this->getDoctrine()->getRepository(GalleryImage::class);
        $images      = $repository->findByEvent($event);
        $galleryHash = $this->galleryHash($event);

        return $this->render(
            'event/admin/gallery.html.twig',
            [
                'event'       => $event,
                'galleryHash' => $galleryHash,
                'images'      => $images
            ]
        );
    }

    /**
     * Page for list of events
     *
     * @ParamConverter("event", class="AppBundle:Event", options={"id" = "eid"})
     * @Route("/admin/event/{eid}/gallery/upload", requirements={"eid": "\d+"}, name="event_gallery_admin_upload")
     * @Security("has_role('ROLE_ADMIN_EVENT')")
     */
    public function uploadImageAction(Request $request, Event $event)
    {
        $token = $request->request->get('token');
        /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        if ($token != $csrf->getToken('gallery-upload-' . $event->getEid())) {
            throw new InvalidTokenHttpException();
        }
        $em = $this->getDoctrine()->getManager();

        if (!$request->files->count()) {
            return new JsonResponse([]);
        }

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            $galleryImage = new GalleryImage($event, $file);
            try {
                $exif = exif_read_data($file->getPathname());
            } catch (\Exception $e) {
                $exif = [];
            }
            try {
                if (isset($exif['DateTimeOriginal'])) {
                    $recorded = new \DateTime($exif['DateTimeOriginal']);
                } elseif (isset($exif['DateTimeDigitized'])) {
                    $recorded = new \DateTime($exif['DateTimeDigitized']);
                } elseif (isset($exif['DateTime'])) {
                    $recorded = new \DateTime($exif['DateTime']);
                } else {
                    $recorded = null;
                }
                $galleryImage->setRecordedAt($recorded);
            } catch (\Exception $e) {
                $this->get('logger')->error($e->getMessage());
            }

            $em->persist($galleryImage);
        }
        $em->flush();

        return new JsonResponse(['eid' => $event->getEid(), 'iid' => $galleryImage->getIid()]);
    }

}