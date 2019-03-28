<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Crud;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mikoweb\SymfonyUtility\Crud\CrudableInterface;
use Mikoweb\SymfonyUtility\Crud\CrudFactoryInterface;
use Mikoweb\SymfonyUtility\Crud\CrudInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CrudFactory implements ContainerAwareInterface, CrudFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
     *
     * @throws \ReflectionException
     */
    public function create(CrudableInterface $object): CrudInterface
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
        $crud = new $class($this->translator);
        $crud->setContainer($this->container);
        $crud->setRelated($object);

        return $crud;
    }
}
