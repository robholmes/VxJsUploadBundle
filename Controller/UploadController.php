<?php

namespace Vx\JsUploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Vx\JsUploadBundle\Uploader\CustomUploadHandler as UploadHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UploadController extends Controller
{
    protected function getUploadOptions($profile)
    {
        $profile = strtolower($profile);

        if ($profile != 'default' && !$this->container->hasParameter('vx_js_upload.profile.'.$profile))
            return false;

        return $profile == 'default' ? UploadHandler::getDefaultOptions() 
                            : $this->container->getParameter('vx_js_upload.profile.'.$profile);
    }

    public function uploadAction($profile, $filename)
    {
        $options = $this->getUploadOptions($profile);

        if ($options == false)
            return new Response('Error: ' . $profile . ' doesn\'t exist');

        $options['filename'] = $filename;

        $callbackService = null;
        if (isset($options['callback_service'])) {
            $callbackService = $options['callback_service'];
            unset($options['callback_service']);
        }

        $handler = new UploadHandler($this->generateUrl('vx_js_delete', array('profile' => $profile)), $options);
        $rawResponse = $handler->post(false);

        if ($callbackService && $this->has($callbackService)) {
            $this->get($callbackService)->afterUpload($profile, $options, $rawResponse);
        }

        return new JsonResponse($rawResponse);
    }

    public function getAction($profile)
    {
        $options = $this->getUploadOptions($profile);

        if ($options == false)
            return new Response('Error profile');

        $handler = new UploadHandler($this->generateUrl('vx_js_delete', array('profile' => $profile)), $options);
        $resp = new JsonResponse($handler->get(false));

        return $resp;
    }

    public function deleteAction($profile, $filename)
    {
        $options = $this->getUploadOptions($profile);
        if ($options == false)
            return new Response('Error: '.$profile.' doesn\'t exist');

        $options['filename'] = $filename;

        $handler = new UploadHandler(null, $options);
        $resp = new JsonResponse($handler->delete(false));

        return $resp;        
    }
}
