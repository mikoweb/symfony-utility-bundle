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

use vSymfo\Component\Document\Format\RssDocument;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class RssDocumentService implements DocumentFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createDocument(array $options = [])
    {
        return new RssDocument();
    }
}
