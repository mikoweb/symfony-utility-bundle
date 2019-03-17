<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Entity\Provider;

use Mikoweb\SymfonyUtilityBundle\Entity\Provider\Exception\EntityViewNotFoundException;
use Mikoweb\SymfonyUtility\Entity\Provider\RendererProviderInterface;

class RendererProvider implements RendererProviderInterface
{
    const DEFAULT_VIEW_KEY = 'default';

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $entitiesViews;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->entitiesViews = [];
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $name, array $context = []): string
    {
        return $this->twig->render($name, $context);
    }

    public function setEntityView(
        string $entityClassName,
        string $viewName,
        string $viewKey = self::DEFAULT_VIEW_KEY
    ): void
    {
        if (!is_string($entityClassName) || empty($entityClassName)) {
            throw new \UnexpectedValueException('Invalid class name');
        }

        if (!is_string($viewName)) {
            throw new \UnexpectedValueException('Invalid view name');
        }

        if (!is_string($viewKey) || empty($viewKey)) {
            throw new \UnexpectedValueException('Invalid key');
        }

        if (!isset($this->entitiesViews[$entityClassName])) {
            $this->entitiesViews[$entityClassName] = [];
        }

        $this->entitiesViews[$entityClassName][$viewKey] = $viewName;
    }

    /**
     * Gets the view of entity.
     *
     * @param string $entityClassName Class name of entity.
     * @param string $viewKey         Key of view.
     *
     * @return string|null
     *
     * @throws EntityViewNotFoundException
     */
    public function getEntityView(string $entityClassName, string $viewKey = self::DEFAULT_VIEW_KEY): ?string
    {
        if (!isset($this->entitiesViews[$entityClassName]) || !isset($this->entitiesViews[$entityClassName][$viewKey])) {
            $exception = new EntityViewNotFoundException();
            $exception->setClassName($entityClassName);
            $exception->setKey($viewKey);
            throw $exception;
        }

        return $this->entitiesViews[$entityClassName][$viewKey];
    }
}
