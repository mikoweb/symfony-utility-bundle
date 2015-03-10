<?php

/*
 * This file is part of the vSymfo package.
 *
 * website: www.vision-web.pl
 * (c) Rafał Mikołajun <rafal@vision-web.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace vSymfo\Bundle\CoreBundle\EventListener;

use CCDNUser\SecurityBundle\Component\Listener\AccessDeniedExceptionFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Przyechwytywanie niektórych wyjątków w celu wyświetlenia spersonalizowanej strony błędu.
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage EventListener
 */
class ExceptionListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->loginBlockedException($event);
    }

    /**
     * Wyświetl komunikat o czasowym zablokowaniu możliwości logowania
     * @param GetResponseForExceptionEvent $event
     */
    private function loginBlockedException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $factory = new AccessDeniedExceptionFactory();
        $cmp = $factory->createAccessDeniedException();
        if (get_class($exception) === get_class($cmp) && $cmp->getCode() === $exception->getCode() && $cmp->getMessage() === $exception->getMessage()) {
            $event->setResponse(new RedirectResponse($this->container->get('router')->generate("vsymfo_exception_login_blocked")));
        }
    }
}
