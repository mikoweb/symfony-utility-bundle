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

use JMS\I18nRoutingBundle\Router\I18nRouter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use vSymfo\Bundle\CoreBundle\Service\Document\DocumentFactoryInterface;
use vSymfo\Component\Document\Format;

/**
 * Create document.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage EventListener
 */
class DocumentListener implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string|null
     */
    protected $forceFormat;

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @param string|null $forceFormat
     * @param string $serviceName
     */
    public function __construct($forceFormat = null, $serviceName = 'document')
    {
        if (!is_string($serviceName)) {
            throw new \InvalidArgumentException('serviceName is not string');
        }
 
        $this->forceFormat = is_string($forceFormat) ? $forceFormat : null;
        $this->serviceName = $serviceName;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->router = $container->get('router');
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $format = null;

        if (is_string($this->forceFormat)) {
            $format = $this->forceFormat;
        } else {
            $request = $event->getRequest();

            if ($this->router instanceof I18nRouter) {
                $collection = $this->router->getOriginalRouteCollection();
            } else {
                $collection = $this->router->getRouteCollection();
            }

            $route = $collection->get($request->get('_route'));

            if (!empty($route))  {
                $defaultFormat = is_null($route->getDefault('_format')) ? 'html' : $route->getDefault('_format');
                $format = !is_null($request->attributes->get('_format')) ? $request->attributes->get('_format') : $defaultFormat;
            }
        }

        if (!is_null($format)) {
            $serviceName = 'vsymfo_core.service.' . strtolower($format) . '_document';

            if ($this->container->has($serviceName)
                && $this->container->get($serviceName) instanceof DocumentFactoryInterface
            ) {
                $service = $this->container->get($serviceName);
            } else {
                $service = $this->container->get('vsymfo_core.service.txt_document');
            }

            $doc = $service->createDocument();
            $this->container->get('vsymfo_core.service.document')->setDefaultsToDocument($doc);
            $this->container->set($this->serviceName, $doc);
        }
    }
}
