<?php

namespace NaxCrmBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The ResponseListener class handles the Response core event as well as the "@extra:Template" annotation.
 */
class ResponseListener implements EventSubscriberInterface
{
    public function onKernelView(GetResponseForControllerResultEvent $event) {
        $view = $event->getControllerResult();
        if (is_array($view)) {
            $view['data'] = $view;
            $view['status'] = 200;
            $view['statusText'] = 'OK';
            $view = new JsonResponse($view);
            $event->setControllerResult($view);
        }
    }
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => ['onKernelView', 50],
        );
    }
}
