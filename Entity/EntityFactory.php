<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Entity;

use Mikoweb\SymfonyUtilityBundle\Entity\Provider\RendererProvider;
use Mikoweb\SymfonyUtilityBundle\Entity\Provider\UrlProvider;
use Mikoweb\SymfonyUtility\Entity\EntityFactoryAbstract;
use Mikoweb\SymfonyUtility\Entity\EntityFactoryInterface;
use Mikoweb\SymfonyUtility\Entity\Interfaces\RendererProviderAwareInterface;
use Mikoweb\SymfonyUtility\Entity\Interfaces\UrlProviderAwareInterface;

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
     * @var RendererProvider
     */
    private $rendererProvider;

    /**
     * @var UrlProvider
     */
    private $urlProvider;

    /**
     * @param RendererProvider $rendererProvider
     * @param $urlProvider
     */
    public function __construct(RendererProvider $rendererProvider, UrlProvider $urlProvider)
    {
        $this->rendererProvider = $rendererProvider;
        $this->urlProvider = $urlProvider;

        $this->awareInterfaces = [
            RendererProviderAwareInterface::class => 'rendererProviderAware',
            UrlProviderAwareInterface::class => 'urlProviderAware',
        ];

        $this->relatedFactories = [];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
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

    public function addFactory(EntityFactoryInterface $factory): void
    {
        if ($factory === $this) {
            throw new \UnexpectedValueException('You can not add yourself!');
        }

        if (!in_array($factory, $this->relatedFactories, true)) {
            $this->relatedFactories[] = $factory;   
        }
    }

    protected function rendererProviderAware(RendererProviderAwareInterface $entity): void
    {
        $entity->setRendererProvider($this->rendererProvider);
    }

    protected function urlProviderAware(UrlProviderAwareInterface $entity): void
    {
        $entity->setUrlProvider($this->urlProvider);
    }
}
