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
use Liip\ThemeBundle\ActiveTheme;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom themes groups.
 * Support for routing option `theme_group`.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage EventListener
 */
class ThemeGroupListener implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ActiveTheme
     */
    protected $theme;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param ActiveTheme $theme
     * @param RouterInterface $router
     */
    public function __construct(ActiveTheme $theme, RouterInterface $router)
    {
        $this->theme = $theme;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->router instanceof I18nRouter) {
            $collection = $this->router->getOriginalRouteCollection();
        } else {
            $collection = $this->router->getRouteCollection();
        }

        $route = $collection->get($request->get('_route'));

        if (!empty($route) && $route->hasOption('theme_group'))  {
            $group = $route->getOption('theme_group');
            $this->theme->setName($group . '_' . $this->container->getParameter('vsymfo_core.theme_' . $group));
        }
    }
}
