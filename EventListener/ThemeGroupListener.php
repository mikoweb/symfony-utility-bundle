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

use Liip\ThemeBundle\ActiveTheme;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rozszerzenie bundla LiipThemeBundle o możliwość grupowania motywów.
 * Na przykład: panel administracyjny powinien mieć osobny motyw graficzny.
 * W tym celu w rutingu trzeba umieścić wpis: option.theme_group: nazwa_grupy.
 * Zostanie wtedy załadowany szablon o nazwie: nazwa_grupy_ + container->getParameter('app_core.theme_' + nazwa_grupy)
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage EventListener
 */
class ThemeGroupListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Usługa Liip Theme
     * @var ActiveTheme
     */
    protected $theme;

    /**
     * Router
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->theme = $container->get('liip_theme.active_theme');
        $this->router = $container->get('router');
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $collection = $this->router->getOriginalRouteCollection();
        $route = $collection->get($request->get('_route'));
        if (empty($route)) {
            $route = $collection->get($request->get('_locale').'__RG__'.$request->get('_route'));
        }

        if (!empty($route) && $route->hasOption('theme_group'))  {
            $group = $route->getOption('theme_group');
            $this->theme->setName($group . '_' . $this->container->getParameter('app_core.theme_' . $group));
        }
    }
}
