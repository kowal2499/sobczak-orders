<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function onResponseEvent(ResponseEvent $event)
    {
        // save new cookie
        if ($event->getRequest()->query->get('locale')) {
            $cookie = new Cookie('locale', $event->getRequest()->getLocale(), time() + (10*365*24*60*60));
            $event->getResponse()->headers->setCookie($cookie);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ResponseEvent::class => 'onResponseEvent',
        ];
    }
}
