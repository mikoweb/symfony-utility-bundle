<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CrudLoader extends Loader implements ContainerAwareInterface
{
    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
            $routePrefix = $this->routePrefix($options['route_prefix']);

            foreach ($collection->all() as $name => $route) {
                $value = $route->getDefault('_controller');
                if (strpos($value, ':') === false) {
                    $route->setDefault('_controller', $controller . ':' . $value);
                }

                if (strpos($name, '_') === 0) {
                    $collection->add($routePrefix . $name, $route);
                    $collection->remove($name);
                }
            }

            if (!in_array('read', $except)) {
                if (!in_array('index', $except)) {
                    $index = $this->indexRoute($options);
                    if (isset($index['root'])) {
                        $collection->add($routePrefix . '_root', $index['root']);
                    }

                    if (isset($index['index'])) {
                        $collection->add($routePrefix . '_index', $index['index']);
                    }
                }

                if (!in_array('show', $except)) {
                    $collection->add($routePrefix . '_show', $this->showRoute($options));
                }
            }

            if (!in_array('create', $except)) {
                $collection->add($routePrefix . '_create', $this->createRoute($options));
                $collection->add($routePrefix . '_store', $this->storeRoute($options));
            }

            if (!in_array('update', $except)) {
                $collection->add($routePrefix . '_edit', $this->editRoute($options));
                $collection->add($routePrefix . '_update', $this->updateRoute($options));
            }

            if (!in_array('delete', $except)) {
                $collection->add($routePrefix . '_destroy', $this->destroyRoute($options));
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
    protected function includeResource(RouteCollection $collection, $resource): void
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

    protected function getOptionsResolver(): OptionsResolver
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
    protected function indexRoute(array $options): array
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
            $root->setDefault('route', $this->routePrefix($options['route_prefix']) . '_index');
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
    protected function indexSortParams(array $options): array
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
    protected function addSortDefaults(Route $route, array $params): void
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
    protected function createRoute(array $options): Route
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
    protected function storeRoute(array $options): Route
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
    protected function showRoute(array $options): Route
    {
        $route = new Route('/{id}');
        $route->setMethods(['GET']);
        $route->setDefault('_controller', $options['controller'] . ':show');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function editRoute(array $options): Route
    {
        $route = new Route('/{id}/edit');
        $route->setMethods(['GET']);
        $route->setDefault('_controller', $options['controller'] . ':edit');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function updateRoute(array $options): Route
    {
        $route = new Route('/{id}/update');
        $route->setMethods(['POST']);
        $route->setDefault('_controller', $options['controller'] . ':update');

        return $route;
    }

    /**
     * @param array $options
     *
     * @return Route
     */
    protected function destroyRoute(array $options): Route
    {
        $route = new Route('/{id}/destroy');
        $route->setMethods(['GET', 'POST']);
        $route->setDefault('_controller', $options['controller'] . ':destroy');

        return $route;
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function routePrefix(string $prefix): string
    {
        if (strpos($prefix, '%') === 0 && strrpos($prefix, '%') === strlen($prefix) - 1) {
            $prefix = $this->container->getParameter(substr($prefix, 1, -1));
        }

        return $prefix;
    }
}
