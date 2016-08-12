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

namespace vSymfo\Bundle\CoreBundle\Controller;

use vSymfo\Bundle\CoreBundle\Crud\Crud;
use vSymfo\Bundle\CoreBundle\Crud\CrudFactory;
use vSymfo\Core\Controller;
use vSymfo\Core\Crud\CrudableInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Controller
 */
abstract class CrudableControllerAbstract extends Controller implements CrudableInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCrudClass()
    {
        return Crud::class;
    }

    /**
     * Returns CRUD factory. 
     *
     * @return CrudFactory
     */
    protected function crudFactory()
    {
        return $this->get('vsymfo_core.crud_factory');
    }
}
