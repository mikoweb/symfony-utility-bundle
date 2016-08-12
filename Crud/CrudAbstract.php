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

namespace vSymfo\Bundle\CoreBundle\Crud;

use JMS\I18nRoutingBundle\Router\I18nRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use vSymfo\Core\Crud\CrudableInterface;
use vSymfo\Core\Crud\CrudInterface;
use vSymfo\Core\Crud\DataEvent;
use vSymfo\Core\Crud\DataInterface;
use vSymfo\Core\Manager\ControllerManagerInterface;

/**
 * Common CRUD that contains Crudable object.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Crud
 */
abstract class CrudAbstract implements CrudInterface
{
    const FLASH_SUCCESS = 'success';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CrudableInterface
     */
    protected $related;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @var array
     */
    private $options;

    public function __construct()
    {
        $this->options = [];
        $resolver = new OptionsResolver();
        $this->optionsResolver = $resolver;
        $resolver->setRequired(['manager', 'route_prefix']);
        $resolver->setDefaults([
            'flash_prefix' => '',
            'message_prefix' => '',
            'message_domain' => null,
            'message_parameters' => [],
            'route_params' => function ($entity) {
                if (!is_callable([$entity, 'getId'])) {
                    throw new \UnexpectedValueException('Not found getId()');
                }

                return ['id' => (string) $entity->getId()];
            }
        ]);
        $resolver->setAllowedTypes('manager', ControllerManagerInterface::class);
        $resolver->setAllowedTypes('route_prefix', 'string');
        $resolver->setAllowedTypes('flash_prefix', 'string');
        $resolver->setAllowedTypes('message_prefix', 'string');
        $resolver->setAllowedTypes('message_domain', ['string', 'null']);
        $resolver->setAllowedTypes('message_parameters', 'array');
        $resolver->setAllowedTypes('route_params', 'callable');
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return CrudableInterface
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * @param CrudableInterface $related
     */
    public function setRelated(CrudableInterface $related)
    {
        $this->related = $related;
        $this->options = $this->optionsResolver->resolve($related->getCrudOptions());
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return ControllerManagerInterface
     */
    public function getManager()
    {
        return $this->options['manager'];
    }

    /**
     * Returns flash type for addFlash method.
     *
     * @param string $type e.g. success
     *
     * @return string
     */
    public function flashType($type)
    {
        return empty($this->options['flash_prefix']) ? $type : ($this->options['flash_prefix'] . '_' . $type);
    }

    /**
     * Returns translated message.
     *
     * @param string $id The message id.
     *
     * @return string
     */
    public function message($id)
    {
        $name = empty($this->options['message_prefix']) ? $id : ($this->options['message_prefix'] . '.' . $id);

        return $this->container->get('translator')->trans($name, $this->options['message_parameters'],
            $this->options['message_domain']);
    }

    /**
     * Add message to flash.
     *
     * @param string $type      $type e.g. success
     * @param string $messageId The message id.
     */
    public function addFlash($type, $messageId)
    {
        $this->container->get('session')->getFlashBag()->add($this->flashType($type), $this->message($messageId));
    }

    /**
     * @param EventDispatcher $dispatcher
     * @param string $name
     * @param DataInterface $data
     *
     * @return DataEvent
     */
    protected function dispatch(EventDispatcher $dispatcher, $name, DataInterface $data)
    {
        $event = new DataEvent($data);
        $dispatcher->dispatch($name, $event);

        return $event;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route         The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string $url    The URL to redirect to
     * @param int    $status The status code to use for the Response
     *
     * @return RedirectResponse
     */
    protected function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route      The name of the route
     * @param array  $parameters An array of parameters
     * @param int    $status     The status code to use for the Response
     *
     * @return RedirectResponse
     */
    protected function redirectToRoute($route, array $parameters = [], $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Returns params required to generate url eq. edit route.
     *
     * @param object $entity
     * @param \Closure $closure Custom params generator.
     *
     * @return array
     */
    protected function routeParams($entity, \Closure $closure = null)
    {
        if (is_null($closure)) {
            $params = $this->options['route_params']($entity);
        } else {
            $params = $closure($entity);
        }

        return $params;
    }

    /**
     * @return string
     */
    protected function indexRoute()
    {
        return $this->options['route_prefix'] . '_index';
    }

    /**
     * @return string
     */
    protected function createRoute()
    {
        return $this->options['route_prefix'] . '_create';
    }

    /**
     * @return string
     */
    protected function storeRoute()
    {
        return $this->options['route_prefix'] . '_store';
    }

    /**
     * @return string
     */
    protected function showRoute()
    {
        return $this->options['route_prefix'] . '_show';
    }

    /**
     * @return string
     */
    protected function editRoute()
    {
        return $this->options['route_prefix'] . '_edit';
    }

    /**
     * @return string
     */
    protected function updateRoute()
    {
        return $this->options['route_prefix'] . '_update';
    }

    /**
     * @return string
     */
    protected function destroyRoute()
    {
        return $this->options['route_prefix'] . '_destroy';
    }

    /**
     * Forward to controller where form is displays.
     *
     * @param Request $request
     * @param string $routeName
     * @param array $parameters
     *
     * @return Response
     *
     * @throws RouteNotFoundException
     */
    protected function formRedirect(Request $request, $routeName, $parameters = [])
    {
        $router = $this->container->get('router');
        if ($router instanceof I18nRouter) {
            $collection = $router->getOriginalRouteCollection();
        } else {
            $collection = $router->getRouteCollection();
        }

        $route = $collection->get($routeName);

        if (is_null($route)) {
            throw new RouteNotFoundException('Not found route "' . $routeName . '"');
        }

        $attributes = array_merge($parameters, [
            '_controller' => $route->getDefault('_controller'),
            '_forwarded' => $request->attributes,
            '_route' => $routeName,
            '_route_params' => $parameters,
        ]);

        $subRequest = $request->duplicate($parameters, null, $attributes);

        return $this->container->get('http_kernel')->handle($subRequest);
    }
}
