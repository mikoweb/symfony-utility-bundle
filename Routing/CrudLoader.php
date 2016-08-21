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

namespace vSymfo\Bundle\CoreBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Routing
 */
class CrudLoader extends Loader
{
    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();
        $this->includeResource($collection, $resource);

        if ($crud = $collection->get('crud')) {
            $collection->remove('crud');
            $collection->addPrefix($crud->getPath());
            $resolver = $this->getOptionsResolver();
            $options = $resolver->resolve($crud->getOptions());
            $controller = $options['controller'];
            $except = $options['except'];

            foreach ($collection->all() as $name => $route) {
                $value = $route->getDefault('_controller');
                if (strpos($value, ':') === false) {
                    $route->setDefault('_controller', $controller . ':' . $value);
                }

                if (strpos($name, '_') === 0) {
                    $collection->add($options['route_prefix'] . $name, $route);
                    $collection->remove($name);
                }
            }

            if (!in_array('read', $except)) {
                if (!in_array('index', $except)) {
                    $index = $this->indexRoute($options);
                    if (isset($index['root'])) {
                        $collection->add($options['route_prefix'] . '_root', $index['root']);
                    }

                    if (isset($index['index'])) {
                        $collection->add($options['route_prefix'] . '_index', $index['index']);
                    }
                }

                if (!in_array('show', $except)) {
                    $collection->add($options['route_prefix'] . '_show', $this->showRoute($options));
                }
            }

            if (!in_array('create', $except)) {
                $collection->add($options['route_prefix'] . '_create', $this->createRoute($options));
                $collection->add($options['route_prefix'] . '_store', $this->storeRoute($options));
            }

            if (!in_array('update', $except)) {
                $collection->add($options['route_prefix'] . '_edit', $this->editRoute($options));
                $collection->add($options['route_prefix'] . '_update', $this->updateRoute($options));
            }

            if (!in_array('delete', $except)) {
                $collection->add($options['route_prefix'] . '_destroy', $this->destroyRoute($options));
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'crud' === $type;
    }

    /**
     * Include a resource.
     *
     * @param RouteCollection $collection Routes collection
     * @param mixed $resource The resource
     */
    protected function includeResource(RouteCollection $collection, $resource)
    {
        switch (pathinfo($resource, PATHINFO_EXTENSION)) {
            case 'yml':
                $collection->addCollection($this->import($resource, 'yaml'));
                break;
            case 'xml':
                $collection->addCollection($this->import($resource, 'xml'));
                break;
        }
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        if (is_null($this->optionsResolver)) {
            $resolver = new OptionsResolver();
            $this->optionsResolver = $resolver;
            $resolver->setRequired(['route_prefix', 'controller']);
            $resolver->setDefaults([
                'compiler_class' => null,
                'except' => [],
                'index_pagination' => false,
                'index_sort' => false,
                'index_sort_params' => [],
            ]);
            $resolver->setAllowedTypes('route_prefix', 'string');
            $resolver->setAllowedTypes('controller', 'string');
            $resolver->setAllowedTypes('except', 'array');
            $resolver->setAllowedTypes('index_pagination', 'bool');
            $resolver->setAllowedTypes('index_sort', 'bool');
            $resolver->setAllowedTypes('index_sort_params', 'array');
        }

        return $this->optionsResolver;
    }

    /**
     * @param array $options
     *
     * @return Route[]
     */
    protected function indexRoute(array $options)
    {
        $routes = [];

        if ($options['index_pagination'] || $options['index_sort']) {
            $path = '/index';
            $defaults = [];
            $requirements = [];
            $sortParams = $this->indexSortParams($options['index_sort_params']);

            if ($options['index_pagination']) {
                $path .= '/{page}';
                $defaults['page'] = '1';
                $requirements['page'] = '\d+';
            }

            if ($options['index_sort']) {
                $path .= '/{sort}/{direction}';
            }

            $route = new Route($path);
            $route->setMethods(['GET']);
            $route->setRequirements($requirements);
            $route->setDefaults($defaults);
            $route->setDefault('_controller', $options['controller'] . ':index');
            $this->addSortDefaults($route, $sortParams);
            $routes['index'] = $route;

            $root = new Route('/');
            $root->setMethods(['GET']);
            $root->setDefault('_controller', 'FrameworkBundle:Redirect:redirect');
            $root->setDefault('route', $options['route_prefix'] . '_index');
            $root->setDefault('_locale', null);
            $routes['root'] = $root;
        } else {
            $route = new Route('/');
            $route->setMethods(['GET']);
            $route->setDefault('_controller', $options['controller'] . ':index');
            $routes['index'] = $route;
        }

        return $routes;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function indexSortParams(array $options)
    {
        $params = [
            'sort' => null,
            'direction' => null,
        ];

        if (isset($options['sort']) && is_string($options['sort'])) {
            $params['sort'] = $options['sort'];
        }

        if (isset($options['direction']) && is_string($options['direction'])) {
            $params['direction'] = $options['direction'];
        }

        return $params;
    }

    /**
     * @param Route $route
     * @param $params
     */
    protected function addSortDefaults(Route $route, array $params)
    {
        if ($params['sort']) {
            $route->setDefault('sort', $params['sort']);
        }

        if ($params['direction']) {
            $route->setDefault('direction', $params['direction']);
        }
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function createRoute(array $options)
    {
        $route = new Route('/create');
        $route->setMethods(['GET']);
        $route->setDefault('_controller', $options['controller'] . ':create');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function storeRoute(array $options)
    {
        $route = new Route('/');
        $route->setMethods(['POST']);
        $route->setDefault('_controller', $options['controller'] . ':store');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function showRoute(array $options)
    {
        $route = new Route('/{id}');
        $route->setMethods(['GET']);
        $route->setDefault('_controller', $options['controller'] . ':show');
        $route->setRequirement('id', '\d+');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function editRoute(array $options)
    {
        $route = new Route('/{id}/edit');
        $route->setMethods(['GET']);
        $route->setDefault('_controller', $options['controller'] . ':edit');
        $route->setRequirement('id', '\d+');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function updateRoute(array $options)
    {
        $route = new Route('/{id}/update');
        $route->setMethods(['POST']);
        $route->setDefault('_controller', $options['controller'] . ':update');
        $route->setRequirement('id', '\d+');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function destroyRoute(array $options)
    {
        $route = new Route('/{id}/destroy');
        $route->setMethods(['GET', 'POST']);
        $route->setDefault('_controller', $options['controller'] . ':destroy');
        $route->setRequirement('id', '\d+');

        return $route;
    }
}
