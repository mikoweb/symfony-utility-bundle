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

namespace vSymfo\Bundle\CoreBundle\FileLoader;

use Symfony\Component\Config\FileLocatorInterface;
use vSymfo\Component\Document\FileLoader\ImageResourcesLoaderAbstract;
use vSymfo\Component\Document\ResourceGroups;
use vSymfo\Component\Document\Resources\ImageResourceManager;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage FileLoader
 */
class ImageResourcesLoader extends ImageResourcesLoaderAbstract
{
    /**
     * @var ImageResourceManager
     */
    private $resourceManager;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $fullConfig;

    /**
     * {@inheritdoc}
     */
    public function __construct(FileLocatorInterface $locator, array $options)
    {
        parent::__construct($locator, $options);
        $this->resourceManager = null;
        $this->config = [];
    }

    /**
     * @return ImageResourceManager
     */
    public function getResourceManager()
    {
        return $this->resourceManager;
    }

    public function unsetResourceManager()
    {
        return $this->resourceManager = null;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getFullConfig()
    {
        return $this->fullConfig;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadImages(array $config, array $fullConfig, $type = null)
    {
        $this->config = $config;
        $this->fullConfig = $fullConfig;
        $this->resourceManager = new ImageResourceManager(new ResourceGroups());
    }
}
