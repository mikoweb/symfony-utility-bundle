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
use Stringy\Stringy as S;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use vSymfo\Bundle\CoreBundle\FileLoader\ImageResourcesLoader;
use vSymfo\Bundle\CoreBundle\Service\ImagesMappingService;
use vSymfo\Component\Document\Resources\ImageResource;
use vSymfo\Component\Document\Resources\ImageResourceManager;
use vSymfo\Core\ApplicationPaths;
use vSymfo\Core\Caller\CallerException;
use vSymfo\Core\Caller\CallerInterface;
use vSymfo\Core\Entity\Provider\ImagesProviderInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Entity_Provider
 */
class ImagesProvider implements ImagesProviderInterface, CallerInterface
{
    const DEFAULT_LAYOUT_NAME = '{DEFAULT}';

    /**
     * @var Packages
     */
    protected $assetPackages;

    /**
     * @var UploaderHelper
     */
    protected $uploaderHelper;

    /**
     * @var ApplicationPaths
     */
    protected $appPaths;

    /**
     * @var FilterConfiguration
     */
    protected $filterConfiguration;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var ImagesMappingService
     */
    protected $mappingService;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var array
     */
    private $loaders;

    /**
     * @var array
     */
    private $resourceManagers;

    /**
     * @param Packages $assetPackages
     * @param UploaderHelper $uploaderHelper
     * @param ApplicationPaths $appPaths
     * @param FilterConfiguration $filterConfiguration,
     * @param CacheManager $cacheManager
     * @param ImagesMappingService $mappingService,
     * @param string $environment
     */
    public function __construct(
        Packages $assetPackages,
        UploaderHelper $uploaderHelper,
        ApplicationPaths $appPaths,
        FilterConfiguration $filterConfiguration,
        CacheManager $cacheManager,
        ImagesMappingService $mappingService,
        $environment
    ) {
        $this->assetPackages = $assetPackages;
        $this->uploaderHelper = $uploaderHelper;
        $this->appPaths = $appPaths;
        $this->filterConfiguration = $filterConfiguration;
        $this->cacheManager = $cacheManager;
        $this->mappingService = $mappingService;
        $this->environment = $environment;
        $this->loaders = [];
        $this->resourceManagers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function asset($obj, $fieldName)
    {
        return $this->uploaderHelper->asset($obj, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function assetOrDefault($obj, $fieldName)
    {
        $path = $this->asset($obj, $fieldName);

        return empty($path) ? $this->getDefaultPath() : $path; 
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPath()
    {
        return self::DEFAULT_PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function imageFilter($path, $filter, array $runtimeConfig = [], $resolver = null)
    {
        return $this->cacheManager->getBrowserPath($path, $filter, $runtimeConfig, $resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($path, $packageName = null)
    {
        return $this->assetPackages->getUrl($path, $packageName);
    }

    /**
     * {@inheritdoc}
     */
    public function render($obj, $fieldName, $format, $layout = null)
    {
        if (!is_object($obj)) {
            throw new \UnexpectedValueException('$obj is not object');
        }

        if (!is_string($fieldName)) {
            throw new \UnexpectedValueException('$fieldName is not string');
        }

        if (empty($fieldName)) {
            throw new \UnexpectedValueException('$fieldName is empty');
        }

        if (!(is_string($layout) || is_null($layout))) {
            throw new \UnexpectedValueException('$layout is not scalar');
        }

        $manager = $this->getResourceManager($obj, $fieldName, $layout);

        return $this->renderImage($manager, $obj, $fieldName, $format, $layout);
    }

    /**
     * The naming convention:
     *
     * imgImagePath                     => assetOrDefault($obj, 'image')
     * imgFieldNamePath                 => assetOrDefault($obj, 'fieldName')
     * imgFieldNamePath_                => asset($obj, 'fieldName')
     *
     * imgImageUrl                      => getUrl(assetOrDefault($obj, 'image'))
     * imgFieldNameUrl                  => getUrl(assetOrDefault($obj, 'fieldName'))
     * imgFieldNameUrl_                 => getUrl(asset($obj, 'fieldName'))
     *
     * imgImage_LightboxFilter          => imageFilter(assetOrDefault($obj, 'image'), 'lightbox')
     * imgFieldName_FilterNameFilter    => imageFilter(assetOrDefault($obj, 'fieldName'), 'filter_name')
     * imgFieldName_FilterNameFilter_   => imageFilter(asset($obj, 'fieldName'), 'filter_name')
     *
     * imgImageRender                   => render($obj, 'image', 'html_picture', null)
     * imgImage__ImgRender              => render($obj, 'image', 'html_img', null)
     * imgImage_IconRender              => render($obj, 'image', 'html_picture', 'icon')
     * imgImage_Icon_ImgRender          => render($obj, 'image', 'html_img', 'icon')
     *
     * {@inheritdoc}
     */
    public function call($obj, $name, array $arguments)
    {
        if (strrpos($name, '_') === strlen($name) - 1) {
            $name = substr($name, 0, -1);
            $orDefault = false;
        } else {
            $orDefault = true;
        }

        $delimit = S::create($name)->delimit('-');
        $arr = explode('-', $delimit);

        if (count($arr) > 2) {
            $prefix = $arr[0];
            $suffix = $arr[count($arr) - 1];

            if ($prefix === $this->callPrefix()) {
                $exception = new CallerException();
                $cut = (string) S::create($name)->slice(strlen($prefix), -strlen($suffix));
                $params = explode('_', $cut);
                $paramsLength = count($params);
                $fieldName = lcfirst($params[0]);

                switch ($suffix) {
                    case 'render':
                        switch ($paramsLength) {
                            case 1:
                                $exception->setReturn($this->render($obj, $fieldName, 
                                    ImageResourceManager::FORMAT_HTML_PICTURE));
                                throw $exception;
                                break;
                            case 2:
                                $exception->setReturn($this->render($obj, $fieldName, 
                                    ImageResourceManager::FORMAT_HTML_PICTURE,
                                    (string) S::create($params[1])->underscored()));
                                throw $exception;
                                break;
                            case 3:
                                $format = strtolower($params[2]);
                                if ($format === 'img' || $format === 'picture') {
                                    $exception->setReturn($this->render($obj, $fieldName,
                                        $format === 'img' 
                                            ? ImageResourceManager::FORMAT_HTML_IMG
                                            : ImageResourceManager::FORMAT_HTML_PICTURE,
                                        (string) S::create($params[1])->underscored()));
                                    throw $exception;
                                }
                                break;
                        }
                        break;
                    case 'filter':
                        if ($paramsLength === 2) {
                            $exception->setReturn($this->imageFilter(
                                $this->assetTernary($obj, $fieldName, $orDefault),
                                (string) S::create($params[1])->underscored(),
                                isset($arguments[0]) && is_array($arguments[0]) ? $arguments[0] : [],
                                isset($arguments[1]) && is_string($arguments[1]) ? $arguments[1] : null
                            ));

                            throw $exception;
                        }
                        break;
                    case 'path':
                        if ($paramsLength === 1) {
                            $exception->setReturn($this->assetTernary($obj, $fieldName, $orDefault));
                            throw $exception;
                        }
                        break;
                    case 'url':
                        if ($paramsLength === 1) {
                            $exception->setReturn($this->getUrl(
                                $this->assetTernary($obj, $fieldName, $orDefault),
                                isset($arguments[0]) && is_string($arguments[0]) ? $arguments[0] : null
                            ));

                            throw $exception;
                        }
                        break;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function callPrefix()
    {
        return 'img';
    }

    /**
     * @param object $obj
     * @param string $fieldName
     * @param bool $orDefault
     *
     * @return string
     */
    protected function assetTernary($obj, $fieldName, $orDefault = false)
    {
        return $orDefault
            ? $this->assetOrDefault($obj, $fieldName)
            : $this->asset($obj, $fieldName);
    }

    /**
     * @param object $obj
     * @param string $fieldName
     *
     * @return ImageResourcesLoader
     */
    protected function getLoader($obj, $fieldName)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $hash = spl_object_hash($obj);

        if (is_null($accessor->getValue($this->loaders, "[$hash][$fieldName]"))) {
            $locator = new FileLocator($this->appPaths->absolute('kernel_root') . ImagesMappingService::CONFIG_DIR);
            $loader = new ImageResourcesLoader($locator, [
                'images_root_dir' => $this->appPaths->absolute('web'),
                'images_output_dir' => $this->appPaths->absolute('web'),
                'baseurl' => $this->appPaths->getBasePath(),
                'cache_dir' => $this->appPaths->absolute('kernel_cache') . ImagesMappingService::CACHE_IMAGES_DIR,
                'cache_refresh' => $this->environment === 'dev',
            ]);

            $accessor->setValue($this->loaders, "[$hash][$fieldName]", $loader);
        }

        return $this->loaders[$hash][$fieldName];
    }

    /**
     * @param object $obj
     * @param string $fieldName
     * @param string $layout
     * 
     * @return ImageResourceManager
     *
     * @throws UndefinedImageMappingException
     */
    protected function getResourceManager($obj, $fieldName, $layout)
    {
        $layout = !is_string($layout) || empty($layout) ? self::DEFAULT_LAYOUT_NAME : $layout;
        $accessor = PropertyAccess::createPropertyAccessor();
        $hash = spl_object_hash($obj);

        if (is_null($accessor->getValue($this->resourceManagers, "[$hash][$fieldName][$layout]"))) {
            $loader = $this->getLoader($obj, $fieldName);
            $loader->load($this->mappingService->getConfigFileName(get_class($obj), $fieldName),
                $layout === self::DEFAULT_LAYOUT_NAME ? null : $layout);

            $accessor->setValue($this->resourceManagers, "[$hash][$fieldName][$layout]", $loader->getResourceManager());
            $loader->unsetResourceManager();
        }

        return $this->resourceManagers[$hash][$fieldName][$layout];
    }

    /**
     * @param ImageResourceManager $manager
     * @param object $obj
     * @param string $fieldName
     * @param string $format
     * @param string|null $layout
     *
     * @return string
     */
    protected function renderImage(ImageResourceManager $manager, $obj, $fieldName, $format, $layout)
    {
        $image = $this->createImageResource($obj, $fieldName, $layout);
        $manager->add($image);
        $render = $manager->render($format);
        $manager->clear();

        return empty($render) ? '' : $render[0];
    }

    /**
     * @param object $obj
     * @param string $fieldName
     * @param string|null $layout
     *
     * @return ImageResource
     */
    protected function createImageResource($obj, $fieldName, $layout)
    {
        $loader = $this->getLoader($obj, $fieldName);
        $config = $loader->getConfig();
        $options = $loader->getOptions();
        $fileName = $this->asset($obj, $fieldName);
        $sources = [$fileName ? $fileName : $this->getDefaultPath()];
        $resource = new ImageResource(
            $this->getAlt($obj, isset($config['alt_property']) ? $config['alt_property'] : null),
            $sources, array_merge([
                'root_dir' => $options['images_root_dir'],
                'output_dir' => $options['images_output_dir'],
            ], $config)
        );

        $resource->setImagesStorage(new ImagesStorage(
            $this->filterConfiguration,
            $this->cacheManager,
            $this->mappingService,
            get_class($obj),
            $fieldName,
            $layout
        ));

        return $resource;
    }

    /**
     * Gets alt attribute for image.
     *
     * @param string $obj
     * @param string $altProperty
     *
     * @return string
     */
    protected function getAlt($obj, $altProperty)
    {
        if (!(is_string($altProperty) || is_null($altProperty))) {
            throw new \UnexpectedValueException('Unexpexted value of altProperty');
        }

        $alt = '';

        if (!empty($altProperty)) {
            $method = 'get' . ucfirst($altProperty);
            if (method_exists($obj, $method)) {
                $alt = $obj->$method();
            }
        }

        return $alt;
    }
}
