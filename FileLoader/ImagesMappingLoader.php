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

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Yaml\Yaml;
use vSymfo\Bundle\CoreBundle\Configuration\ImagesMappingConfiguration;
use vSymfo\Core\FileLoaderAbstract;

/**
 * Used to load configuration of images mapping.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage FileLoader
 */
class ImagesMappingLoader extends FileLoaderAbstract
{
    /**
     * @var array
     */
    private $mappingConfig;

    /**
     * {@inheritdoc}
     */
    protected function refreshCache($filename, ConfigCache $cache)
    {
        $content = Yaml::parse(file_get_contents($filename));
        $resource = new FileResource($filename);
        $processor = new Processor();
        $processor->processConfiguration(new ImagesMappingConfiguration(), is_array($content) ? $content : []);

        $this->writeCache($cache, $resource, $content);
    }

    /**
     * {@inheritdoc}
     */
    protected function process($filename, $type = null)
    {
        $this->mappingConfig = isset(self::$yaml[$filename]) && self::$yaml[$filename]['entities']
            && is_array(self::$yaml[$filename]['entities']) ? self::$yaml[$filename]['entities'] : [];
    }

    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->mappingConfig;
    }
}
