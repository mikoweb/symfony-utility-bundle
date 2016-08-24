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

namespace vSymfo\Bundle\CoreBundle\Service\Document;

use vSymfo\Component\Document\Interfaces\DocumentInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
interface DocumentFactoryInterface
{
    /**
     * Returns document ready to render.
     *
     * @param array $options
     *
     * @return DocumentInterface
     */
    public function createDocument(array $options = []);
}
