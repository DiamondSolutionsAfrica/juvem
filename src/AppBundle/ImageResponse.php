<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ImageResponse
 *
 * @package AppBundle\Response
 */
class ImageResponse extends StreamedResponse
{
    /**
     * @param UploadImage $image
     * @param string      $format
     * @param Request     $request
     * @param int|null    $defaultWidth
     * @param int|null    $defaultHeight
     * @return static
     */
    public static function createFromRequest(
        UploadImage $image,
        $format,
        Request $request,
        $defaultWidth = 128,
        $defaultHeight = 128
    )
    {
        $response = new Response('', Response::HTTP_OK);
        self::setCacheHeaders($image, $response, $request);

        if ($response->isNotModified($request)) {
            return $response;
        }

        //        $image  = $request->query->get('image');
        $width  = $request->query->get('width', $defaultWidth);
        $height = $request->query->get('height', $defaultHeight);

        return new static($image, $format, $width, $height);
    }

    /**
     * @param UploadImage  $image
     * @param Response     $response
     * @param Request|null $request Transmit @see Request in order to be able to do an ETAG compare
     */
    private static function setCacheHeaders(UploadImage $image, Response $response, Request $request = null)
    {
        $response->setEtag($image->getETag())
                 ->setLastModified($image->getMTime())
                 ->setMaxAge(14 * 24 * 60 * 60)
                 ->setPublic();
        if ($request) {
            $response->isNotModified($request);
        }
    }

    /**
     * @param string $format
     * @return string
     */
    private static function getMimeType($format)
    {
        return ($format == 'png') ? 'image/png' : 'image/jpeg';
    }

    /**
     * Constructor
     *
     * @param UploadImage  $image   Image to provide
     * @param Request|null $request Transmit @see Request in order to be able to do an ETAG compare
     */
    public function __construct(UploadImage $image, Request $request = null)
    {
        parent::__construct(
            function () use ($image) {
                echo $image->get();
            },
            Response::HTTP_OK,
            ['Content-Type' => $image->getType(true)]
        );

        self::setCacheHeaders($image, $this, $request);
    }
}
