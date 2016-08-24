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

use ReflectionClass;
use vSymfo\Component\Document\Format\DocumentAbstract;
use vSymfo\Component\Document\Interfaces\DocumentInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class DocumentService
{
    /**
     * @var array
     */
    protected $params;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @param DocumentInterface $document
     */
    public function setDefaultsToDocument(DocumentInterface $document)
    {
        $r = new ReflectionClass(DocumentAbstract::class);
        $mode = $r->getConstant("TITLE_" . strtoupper($this->params["title_mode"]));
        $document->name($this->params["sitename"]);
        $document->title($this->params["title_default"], $mode, $this->params["title_separator"]);
        $document->keywords($this->params["keywords"]);
        $document->description($this->params["description"]);
    }
}
