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
use Mikoweb\SymfonyUtilityBundle\Entity\EntityFactory;

class EntityProvidersSubscriber implements EventSubscriber
{
    /**
     * @var EntityFactory
     */
    protected $entityFactory;

    public function __construct(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

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

    public function getEntityFactory(): EntityFactory
    {
        return $this->entityFactory;
    }
}
