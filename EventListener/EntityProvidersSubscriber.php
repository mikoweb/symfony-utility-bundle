<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mikoweb\SymfonyUtilityBundle\Entity\EntityFactory;

class EntityProvidersSubscriber implements EventSubscriber, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'postLoad',
            'postPersist',
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $this->getEntityFactory()->aware($args->getEntity());
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->getEntityFactory()->aware($args->getEntity());
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getEntityFactory(): EntityFactory
    {
        return $this->container->get('symfony_utility.entity_factory');
    }
}
