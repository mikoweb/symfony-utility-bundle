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

namespace vSymfo\Bundle\CoreBundle\Service;

use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Stringy\StaticStringy as S;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use vSymfo\Bundle\CoreBundle\FileLoader\ImageResourcesLoader;
use vSymfo\Bundle\CoreBundle\FileLoader\ImagesMappingLoader;
use vSymfo\Bundle\CoreBundle\Service\Exception\UndefinedImageMappingException;
use vSymfo\Component\Document\Resources\ImageResource;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class ImagesMappingService
{
    const CONFIG_DIR = '/images';
    const CACHE_MAPPING_DIR = '/vsymfo_core_images_mapping';
    const CACHE_IMAGES_DIR = '/vsymfo_core_images';

    /**
     * @var ImagesMappingLoader
     */
    private $loader;

    /**
     * @var ApplicationPaths
     */
    private $appPaths;

    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param string $configFileName
     * @param ApplicationPaths $appPaths
     * @param FilterConfiguration $filterConfiguration,
     * @param $environment
     */
    public function __construct(
        $configFileName, 
        ApplicationPaths $appPaths,
        FilterConfiguration $filterConfiguration,
        $environment
    ) {
        $this->appPaths = $appPaths;
        $this->filterConfiguration = $filterConfiguration;
        $this->environment = $environment;
        $this->initLoader($configFileName);
        $this->setUpImagineFilters();
    }

    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->loader->getMappingConfig();
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     *
     * @throws UndefinedImageMappingException
     */
    public function getConfigFileName($className, $fieldName)
    {
        $mappingConfig = $this->getMappingConfig();
        if (!isset($mappingConfig[$className]) || !isset($mappingConfig[$className][$fieldName])) {
            throw new UndefinedImageMappingException("Undefined: $className::$fieldName");
        }

        return $mappingConfig[$className][$fieldName];
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function getImageResourceOptions(array $config)
    {
        $resource = new ImageResource('', $config['images'], array_merge(['root_dir' => '', 'output_dir' => ''], $config));
        return $resource->getOptions();
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string|null $layout
     * @param array $imageOptions
     *
     * @return string
     */
    public function getImagineFilterName($className, $fieldName, $layout, array $imageOptions)
    {
        $fileInfo = pathinfo($this->getConfigFileName($className, $fieldName));
        $storageName = $fileInfo['filename'];

        if ($fileInfo['dirname'] !== '.') {
            $storageName = $fileInfo['dirname'] . '/' . $storageName;
        }

        $storageName .= '_' . (empty($layout) ? '' : ('--' . $layout . '_')) . $imageOptions['width'] . '_' . $imageOptions['height'];

        return (string) S::underscored(str_replace(['/', '\\', '.'], '_', $storageName));
    }

    /**
     * @param string $filter
     * @param array $imageOptions
     *
     * @return array
     */
    public function getImagineFilterConfig($filter, array $imageOptions)
    {
        $filterConfig = $this->filterConfiguration->get($filter);

        if (isset($filterConfig['filters']['thumbnail'])) {
            $filterConfig['filters']['thumbnail']['size'] = [$imageOptions['width'], $imageOptions['height']];
        }

        if (isset($filterConfig['filters']['relative_resize'])) {
            if (isset($filterConfig['filters']['relative_resize']['heighten'])) {
                $filterConfig['filters']['relative_resize']['heighten'] = $imageOptions['height'];
            }

            if (isset($filterConfig['filters']['relative_resize']['widen'])) {
                $filterConfig['filters']['relative_resize']['widen'] = $imageOptions['width'];
            }
        }

        return $filterConfig;
    }

    private function setUpImagineFilters()
    {
        $mappingConfig = $this->loader->getMappingConfig();
        $locator = new FileLocator($this->getConfigDirectory());
        $resourcesLoader = new ImageResourcesLoader($locator, [
            'images_root_dir' => '',
            'images_output_dir' => '',
            'baseurl' => '',
            'cache_dir' => $this->appPaths->absolute('kernel_cache') . self::CACHE_IMAGES_DIR,
            'cache_refresh' => $this->environment === 'dev',
        ]);

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($mappingConfig as $className => $fields) {
            foreach ($fields as $fieldName => $fileName) {
                $resourcesLoader->load($fileName);
                $this->addImagineFilter($resourcesLoader, $className, $fieldName, null);

                $layouts = $accessor->getValue($resourcesLoader->getFullConfig(), '[images][layout]');
                if (is_array($layouts)) {
                    foreach ($layouts as $layout => $value) {
                        $resourcesLoader->load($fileName, $layout);
                        $this->addImagineFilter($resourcesLoader, $className, $fieldName, $layout);
                    }
                }
            }
        }
    }

    /**
     * @param ImageResourcesLoader $resourcesLoader
     * @param string $className
     * @param string $fieldName
     * @param string|null $layout
     *
     * @return array
     */
    private function addImagineFilter(ImageResourcesLoader $resourcesLoader, $className, $fieldName, $layout)
    {
        $imageOptions = $this->getImageResourceOptions($resourcesLoader->getConfig());
        $addedNames = [];

        foreach ($imageOptions['images'] as $image) {
            $filterName = $this->getImagineFilterName($className, $fieldName, $layout, $image);

            try {
                $this->filterConfiguration->get($filterName);
            } catch (NonExistingFilterException $e) {
                $this->filterConfiguration->set($filterName, $this->getImagineFilterConfig($imageOptions['filter'], $image));
                $addedNames[] = $filterName;
            }
        }

        return $addedNames;
    }

    /**
     * @param $fileName
     */
    private function initLoader($fileName)
    {
        $locator = new FileLocator($this->getConfigDirectory());
        $this->loader = new ImagesMappingLoader($locator, [
            'cache_refresh' => $this->environment === 'dev',
            'cache_dir' => $this->appPaths->absolute('kernel_cache') . self::CACHE_MAPPING_DIR,
        ]);
        $this->loader->load($fileName);
    }

    /**
     * @return string
     */
    private function getConfigDirectory()
    {
        return $this->appPaths->absolute('kernel_root') . self::CONFIG_DIR;
    }
}
