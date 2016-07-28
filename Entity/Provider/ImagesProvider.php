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

use vSymfo\Core\Entity\Provider\ImagesProviderInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Entity_Provider
 */
class ImagesProvider implements ImagesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function asset($obj, $fieldName)
    {
        // TODO: Implement asset() method.
    }

    /**
     * {@inheritdoc}
     */
    public function render($obj, $fieldName)
    {
        // TODO: Implement render() method.
    }
}
