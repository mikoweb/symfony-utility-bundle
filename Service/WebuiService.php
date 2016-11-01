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

use Liip\ThemeBundle\ActiveTheme;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class WebuiService
{
    /**
     * @var array
     */
    protected $params;

    /**
     * @var ApplicationPaths
     */
    protected $appPaths;

    /**
     * @var ActiveTheme
     */
    protected $theme;

    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    /**
     * @var string
     */
    protected $env;

    /**
     * @param array $params
     * @param ApplicationPaths $appPaths
     * @param ActiveTheme $theme
     * @param RequestStack $requestStack
     * @param string $env
     */
    public function __construct(
        array $params,
        ApplicationPaths $appPaths,
        ActiveTheme $theme,
        RequestStack $requestStack,
        $env
    ) {
        $this->params = $params;
        $this->appPaths = $appPaths;
        $this->theme = $theme;
        $this->request = $requestStack;
        $this->env = $env;
        $this->resolver = new OptionsResolver();
        $this->resolver->setRequired('resources');
        $this->resolver->setRequired('translations');
        $this->resolver->setAllowedTypes('resources', ['array']);
        $this->resolver->setAllowedTypes('translations', ['array']);
    }

    /**
     * Returns json string with WebUI config.
     *
     * @param boolean $useRequirejsRc Is use file .requirejsrc.
     * @param string $jsonFilePath    Path to parse json file with WebUI config.
     * @param array $options          Required options eg. resources, translations.
     * @param array $extend           Additional params.
     *
     * @return string
     */
    public function startApp($useRequirejsRc, $jsonFilePath, array $options, array $extend = [])
    {
        $path = $this->getCacheFileName($jsonFilePath);
        $cache = new ConfigCache($path, $this->env === 'dev');

        if (!$cache->isFresh()) {
            $resource = new FileResource($this->getSrcFileName($jsonFilePath));
            $resourceRc = new FileResource($this->getRequirejsRcFile());
            $content = $this->generateConfig($useRequirejsRc, $jsonFilePath, $options, $extend);
            $cache->write($content, [$resource, $resourceRc]);
        }

        return file_get_contents($path);
    }

    /**
     * @param boolean $useRequirejsRc
     * @param string $jsonFilePath
     * @param array $options
     * @param array $extend
     *
     * @return string
     */
    protected function generateConfig($useRequirejsRc, $jsonFilePath, array $options, array $extend = [])
    {
        $jsonFile = $this->getSrcFileName($jsonFilePath);

        if (!file_exists($jsonFile)) {
            throw new \UnexpectedValueException('Not found file: ' . $jsonFile);
        }

        $json = json_decode(file_get_contents($jsonFile), true);

        if (!is_array($json)) {
            throw new \UnexpectedValueException('File ' . $jsonFile . ' is invalid.');
        }

        if ($useRequirejsRc) {
            $rcFile = $this->getRequirejsRcFile();

            if (!file_exists($rcFile)) {
                throw new \UnexpectedValueException('Not found file: ' . $rcFile);
            }

            $rcData = json_decode(file_get_contents($rcFile), true);

            if (!is_array($rcData)) {
                throw new \UnexpectedValueException('File ' . $rcFile . ' is invalid.');
            }
        } else {
            $rcData = [];
        }

        $params = $this->resolver->resolve($options);
        $basePath = $this->appPaths->getBasePath();
        $defaults = [
            'timeout' => (int) $this->params['resources_loading_timeout'],
            'res' => $params['resources'],
            'translations' => $params['translations'],
            'locale' => $this->request->getCurrentRequest()->getLocale(),
            'theme_name' => $this->theme->getName(),
            'path' => [
                'base' => $basePath,
                'theme' => $this->appPaths->url('web_theme'),
                'resources' => $this->appPaths->url('web_resources'),
                'lib' => $this->appPaths->url('web_resources') . '/lib',
                'cdn_javascript' => $this->params['cdn_enable'] ? $this->params['cdn_javascript'] : '',
                'cdn_css' => $this->params['cdn_enable'] ? $this->params['cdn_css'] : '',
                'cdn_image' => $this->params['cdn_enable'] ? $this->params['cdn_image'] : '',
            ],
            'requirejs' => [
                'baseUrl' => $basePath . '/js',
            ]
        ];

        $config = array_merge_recursive($defaults, ['requirejs' => $rcData], $json, $extend);

        return json_encode($config, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getCacheFileName($fileName)
    {
        return $this->appPaths->getCacheDir() . '/vsymfo_webui_config/' . $this->theme->getName() . '/' .
            str_replace(['/', '\\', '.'], '_', $fileName);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getSrcFileName($fileName)
    {
        return $this->appPaths->getRootDir() . '/' . $fileName;
    }

    /**
     * @return string
     */
    protected function getRequirejsRcFile()
    {
        return $this->appPaths->getRootDir() . '/../.requirejsrc';
    }
}
