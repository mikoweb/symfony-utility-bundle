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

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use vSymfo\Core\Crud\CrudableInterface;
use vSymfo\Core\Crud\CrudFactoryInterface;
use vSymfo\Core\Crud\CrudInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Crud
 */
class CrudFactory implements ContainerAwareInterface, CrudFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    public function create(CrudableInterface $object)
    {
        $class = $object->getCrudClass();

        if (!class_exists($class)) {
            throw new \InvalidArgumentException('Not found CRUD class.');
        }

        $reflector = new \ReflectionClass($class);

        if (!$reflector->implementsInterface(CrudInterface::class)) {
            throw new \InvalidArgumentException('Class is not implement CrudInterface.');
        }

        /** @var CrudInterface $crud */
        $crud = new $class();
        $crud->setContainer($this->container);
        $crud->setRelated($object);

        return $crud;
    }
}
