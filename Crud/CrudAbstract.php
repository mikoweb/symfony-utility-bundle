<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Crud;

use JMS\I18nRoutingBundle\Router\I18nRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mikoweb\SymfonyUtility\Crud\CrudableInterface;
use Mikoweb\SymfonyUtility\Crud\CrudInterface;
use Mikoweb\SymfonyUtility\Crud\DataEvent;
use Mikoweb\SymfonyUtility\Crud\DataInterface;
use Mikoweb\SymfonyUtility\Manager\ControllerManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * @var array
     */
    private $options;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

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
     * {@inheritdoc}
     */
    public function getRelated(): CrudableInterface
    {
        return $this->related;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelated(CrudableInterface $related): void
    {
        $this->related = $related;
        $this->options = $this->optionsResolver->resolve($related->getCrudOptions());
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getManager(): ControllerManagerInterface
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
    public function flashType(string $type): string
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
    public function message(string $id): string
    {
        $name = empty($this->options['message_prefix']) ? $id : ($this->options['message_prefix'] . '.' . $id);

        return $this->translator->trans($name, $this->options['message_parameters'], $this->options['message_domain']);
    }

    /**
     * Add message to flash.
     *
     * @param string $type      $type e.g. success
     * @param string $messageId The message id.
     */
    public function addFlash(string $type, string $messageId): void
    {
        $this->container->get('session')->getFlashBag()->add($this->flashType($type), $this->message($messageId));
    }

    /**
     * {@inheritdoc}
     */
    public function indexRoute(): string
    {
        return $this->options['route_prefix'] . '_index';
    }

    /**
     * {@inheritdoc}
     */
    public function createRoute(): string
    {
        return $this->options['route_prefix'] . '_create';
    }

    /**
     * {@inheritdoc}
     */
    public function storeRoute(): string
    {
        return $this->options['route_prefix'] . '_store';
    }

    /**
     * {@inheritdoc}
     */
    public function showRoute(): string
    {
        return $this->options['route_prefix'] . '_show';
    }

    /**
     * {@inheritdoc}
     */
    public function editRoute(): string
    {
        return $this->options['route_prefix'] . '_edit';
    }

    /**
     * {@inheritdoc}
     */
    public function updateRoute(): string
    {
        return $this->options['route_prefix'] . '_update';
    }

    /**
     * {@inheritdoc}
     */
    public function destroyRoute(): string
    {
        return $this->options['route_prefix'] . '_destroy';
    }

    protected function dispatch(EventDispatcher $dispatcher, string $name, DataInterface $data): DataEvent
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
    protected function generateUrl(
        string $route,
        $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string
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
    protected function redirect(string $url, int $status = 302): RedirectResponse
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
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
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
    protected function routeParams($entity, \Closure $closure = null): array
    {
        if (is_null($closure)) {
            $params = $this->options['route_params']($entity);
        } else {
            $params = $closure($entity);
        }

        return $params;
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
    protected function formRedirect(Request $request, string $routeName, array $parameters = []): Response
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
