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

namespace vSymfo\Bundle\CoreBundle\Entity;

use vSymfo\Bundle\CoreBundle\Entity\Provider\ImagesProvider;
use vSymfo\Bundle\CoreBundle\Entity\Provider\RendererProvider;
use vSymfo\Bundle\CoreBundle\Entity\Provider\UrlProvider;
use vSymfo\Core\Entity\EntityFactoryAbstract;
use vSymfo\Core\Entity\EntityFactoryInterface;
use vSymfo\Core\Entity\Interfaces\ImagesProviderAwareInterface;
use vSymfo\Core\Entity\Interfaces\RendererProviderAwareInterface;
use vSymfo\Core\Entity\Interfaces\UrlProviderAwareInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Entity
 */
class EntityFactory extends EntityFactoryAbstract
{
    /**
     * @var array
     */
    private $awareInterfaces;

    /**
     * @var EntityFactoryInterface[]
     */
    private $relatedFactories;

    /**
     * @var ImagesProvider
     */
    private $imagesProvider;

    /**
     * @var RendererProvider
     */
    private $rendererProvider;

    /**
     * @var UrlProvider
     */
    private $urlProvider;

    /**
     * @param ImagesProvider $imagesProvider
     * @param RendererProvider $rendererProvider
     * @param $urlProvider
     */
    public function __construct(ImagesProvider $imagesProvider, RendererProvider $rendererProvider, $urlProvider)
    {
        $this->imagesProvider = $imagesProvider;
        $this->rendererProvider = $rendererProvider;
        $this->urlProvider = $urlProvider;

        $this->awareInterfaces = [
            ImagesProviderAwareInterface::class => 'imagesProviderAware',
            RendererProviderAwareInterface::class => 'rendererProviderAware',
            UrlProviderAwareInterface::class => 'urlProviderAware',
        ];

        $this->relatedFactories = [];
    }

    /**
     * {@inheritdoc}
     */
    public function aware($entity)
    {
        $reflection = new \ReflectionClass(get_class($entity));
        foreach ($reflection->getInterfaceNames() as $interfaceName) {
            if (isset($this->awareInterfaces[$interfaceName])) {
                $methodName = $this->awareInterfaces[$interfaceName];
                if (method_exists($this, $methodName)) {
                    $this->$methodName($entity);
                } else {
                    foreach ($this->relatedFactories as $factory) {
                        $factory->aware($entity);
                    }
                }
            }
        }
    }

    /**
     * @param EntityFactoryInterface $factory
     */
    public function addFactory(EntityFactoryInterface $factory)
    {
        if ($factory === $this) {
            throw new \UnexpectedValueException('You can not add yourself!');
        }

        if (!in_array($factory, $this->relatedFactories, true)) {
            $this->relatedFactories[] = $factory;   
        }
    }

    /**
     * @param ImagesProviderAwareInterface $entity
     */
    private function imagesProviderAware(ImagesProviderAwareInterface $entity)
    {
        $entity->setImagesProvider($this->imagesProvider);
    }

    /**
     * @param RendererProviderAwareInterface $entity
     */
    private function rendererProviderAware(RendererProviderAwareInterface $entity)
    {
        $entity->setRendererProvider($this->rendererProvider);
    }

    /**
     * @param UrlProviderAwareInterface $entity
     */
    private function urlProviderAware(UrlProviderAwareInterface $entity)
    {
        $entity->setUrlProvider($this->urlProvider);
    }
}
