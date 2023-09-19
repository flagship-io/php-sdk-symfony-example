<?php

namespace App\EventListener;

use Flagship\Flagship;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelTerminateListener implements EventSubscriberInterface
{
    public function onKernelTerminate(TerminateEvent $event): void
    {
        Flagship::close();
    }

    public static function getSubscribedEvents(): array
    {
        return  [
            KernelEvents::TERMINATE => [ 'onKernelTerminate']
        ];
    }
}