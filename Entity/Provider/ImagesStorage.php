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

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use vSymfo\Bundle\CoreBundle\Service\ImagesMappingService;
use vSymfo\Component\Document\Interfaces\UrlManagerInterface;
use vSymfo\Component\Document\Resources\Storage\ImagesStorageInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Entity_Provider
 */
class ImagesStorage implements ImagesStorageInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $sources;

    /**
     * @var UrlManagerInterface
     */
    private $urlManager;

    /**
     * @var array
     */
    private $urls;

    /**
     * @var bool
     */
    private $refresh;

    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ImagesMappingService
     */
    private $mappingService;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string|null
     */
    private $layout;

    /**
     * @param FilterConfiguration $filterConfiguration
     * @param CacheManager $cacheManager
     * @param ImagesMappingService $mappingService
     * @param string $className
     * @param string $fieldName
     * @param string|null $layout
     */
    public function __construct(
        FilterConfiguration $filterConfiguration,
        CacheManager $cacheManager,
        ImagesMappingService $mappingService,
        $className, $fieldName, $layout
    ) {
        $this->filterConfiguration = $filterConfiguration;
        $this->cacheManager = $cacheManager;
        $this->mappingService = $mappingService;
        $this->className = $className;
        $this->fieldName = $fieldName;
        $this->layout = $layout;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls()
    {
        if (is_null($this->urls) || $this->refresh === true) {
            $this->urls = [];
            $this->refresh = false;
            foreach ($this->options['images'] as $image) {
                $filterName = $this->mappingService->getImagineFilterName($this->className, $this->fieldName, 
                    $this->layout, $image);
                $this->urls[] = $this->cacheManager->getBrowserPath($this->sources[$image['index']], $filterName);
            }
        }

        return $this->urls;
    }

    /**
     * {@inheritdoc}
     */
    public function setSources(array $sources = [])
    {
        $this->refresh = true;
        $this->sources = $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options = [])
    {
        $this->refresh = true;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function setUrlManager(UrlManagerInterface $urlManager = null)
    {
        $this->refresh = true;
        $this->urlManager = $urlManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $options = null)
    {
        throw new \RuntimeException('Unimplemented!');
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        throw new \RuntimeException('Unimplemented!');
    }
}
