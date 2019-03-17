<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Controller;

use Mikoweb\SymfonyUtilityBundle\Crud\Crud;
use Mikoweb\SymfonyUtilityBundle\Crud\CrudFactory;

trait CrudableTrait
{
    /**
     * @var CrudFactory
     */
    private $crudFactory;

    /**
     * {@inheritdoc}
     */
    public function getCrudClass(): string
    {
        return Crud::class;
    }

    /**
     * Returns CRUD factory.
     *
     * @return CrudFactory
     */
    protected function crudFactory(): CrudFactory
    {
        return $this->crudFactory;
    }
}
