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

namespace vSymfo\Bundle\CoreBundle\Entity\Provider;

use vSymfo\Bundle\CoreBundle\Entity\Provider\Exception\EntityViewNotFoundException;
use vSymfo\Core\Entity\Provider\RendererProviderInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Entity_Provider
 */
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

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->entitiesViews = [];
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $context = [])
    {
        return $this->twig->render($name, $context);
    }

    /**
     * @param string $entityClassName Class name of entity.
     * @param string $viewName        Name of view.
     * @param string $viewKey         Key of view.
     */
    public function setEntityView($entityClassName, $viewName, $viewKey = self::DEFAULT_VIEW_KEY)
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
    public function getEntityView($entityClassName, $viewKey = self::DEFAULT_VIEW_KEY)
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
