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

use Liip\ThemeBundle\ActiveTheme;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;
use vSymfo\Component\Document\FileLoader\TranslationLoader;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\JavaScriptResourceManager;
use vSymfo\Component\Document\Utility\HtmlResourcesUtility;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class HtmlDocumentService implements DocumentFactoryInterface
{
    /**
     * @var ApplicationPaths
     */
    protected $appPaths;

    /**
     * @var string
     */
    protected $env;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var ActiveTheme
     */
    protected $theme;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ApplicationPaths $appPaths
     * @param string $env
     * @param array $params
     * @param RequestStack $requestStack
     * @param \Twig_Environment $twig
     * @param ActiveTheme $theme
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ApplicationPaths $appPaths,
        $env,
        array $params,
        RequestStack $requestStack,
        \Twig_Environment $twig,
        ActiveTheme $theme,
        TranslatorInterface $translator
    ) {
        $this->appPaths = $appPaths;
        $this->env = $env;
        $this->params = $params;
        $this->requestStack = $requestStack;
        $this->twig = $twig;
        $this->theme = $theme;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function createDocument(array $options = [])
    {
        $document = new HtmlDocument();

        // used by Modernizr
        $document->element('html')->addClass('no-js');

        // meta viewport
        $viewport = $this->params["meta_viewport"];
        if (!empty($viewport)) {
            $meta = $document->element('viewport');
            $meta->attr('content', $viewport);
        }

        // favicon
        $favicon = $document->element('favicon');
        $favicon->enable($this->params["favicon_enable"]);
        $favicon->setBasePath($this->appPaths->url('web_theme') . '/favicons');
        $favicon->setTileColor($this->params["favicon_tile_color"]);
        if (file_exists($this->appPaths->absolute('web_theme') . '/favicons/favicon.html')) {
            $favicon->setFaviconTemplate(file_get_contents($this->appPaths->absolute('web_theme') . '/favicons/favicon.html'));
        }

        // html lang
        $document->element('html')->attr('lang', $this->requestStack->getCurrentRequest()->getLocale());

        // resources
        $utility = $this->getUtility($this->params);
        $utility->createResOnAdd($document, "javascript", "default");
        $utility->createResOnAdd($document, "stylesheet", "default");

        $document->resources("javascript")->chooseOnAdd("default");
        $document->resources("stylesheet")->chooseOnAdd("default");

        $locator = new FileLocator($this->appPaths->getRootDir() . '/document');
        $loader = $utility->createResourcesLoader($document, 'javascript', $locator, '/');
        $loader->load('html_resources.yml', 'framework');
        $loader->load('html_resources.yml', 'core');

        $locator = new FileLocator($this->appPaths->absolute('private_theme'));
        $loader = $utility->createResourcesLoader($document, 'stylesheet', $locator, $this->appPaths->getThemePath() . '/src');
        $loader->load('html_resources.yml', 'theme');
        $loader = $utility->createResourcesLoader($document, 'javascript', $locator, $this->appPaths->getThemePath() . '/src');
        $loader->load('html_resources.yml', 'theme');

        // js initializer
        $jsloader = $this->getJsLoader();
        $script = $document->element('script');
        $twig = $this->twig;
        $params = $this->params;
        $appPaths = $this->appPaths;
        $theme = $this->theme;
        $document->setScriptOutput(function (JavaScriptResourceManager $manager, array $translations)
            use($jsloader, $params, $script, $twig, $appPaths, $theme)
        {
            $output = $jsloader->render('html');
            $output .= '<script type="text/javascript">';
            $output .= $twig->render('::head.js.twig', [
                "resources" => $manager->render('array'),
                "translations" => $translations,
                "timeout" => (int)$params['resources_loading_timeout'],
                "theme_name" => $theme->getName(),
                "path" => [
                    "base" => $appPaths->url("base"),
                    "theme" => $appPaths->url("web_theme"),
                    "resources" => $appPaths->url("web_resources"),
                    "cdn_javascript" => $params['cdn_enable'] ? $params['cdn_javascript'] : '',
                    "cdn_css" => $params['cdn_enable'] ? $params['cdn_css'] : '',
                    "cdn_image" => $params['cdn_enable'] ? $params['cdn_image'] : '',
                ]
            ]);
            $output .= '</script>';

            if (!$script->isEmpty()) {
                $output .= '<script type="text/javascript">' . $script->render() . '</script>';
                $script->update(function ()  {
                    return '';
                });
            }

            return $output;
        });

        // translations used in theme
        if (file_exists($this->appPaths->absolute("kernel_root") . '/theme/' . $this->appPaths->getThemeName() . '/translations.yml')) {
            $trans = $this->translator;
            $locator = new FileLocator($this->appPaths->absolute("kernel_root") . '/theme/' . $this->appPaths->getThemeName());
            $loader = new TranslationLoader($locator, [
                'cache_dir' => $this->appPaths->absolute("kernel_cache") . "/vsymfo_document/translations",
                'cache_refresh' => $this->env == "dev",
                'document' => $document,
                'trans_closure' => function ($text) use($trans) {
                    return $trans->trans($text);
                }
            ]);

            $loader->load('translations.yml');
        }

        return $document;
    }

    /**
     * Return data needed for css preprocessors.
     *
     * @return array
     */
    public function getPreprocessorData()
    {
        $commonUrl = $this->appPaths->url('web_theme') . '/';  

        return [
            'variables' => [
                'path-base'      => '"' . $this->appPaths->url('base') . '"',
                'path-theme'     => '"' . $this->appPaths->url('web_theme') . '"',
                'path-resources' => '"' . $this->appPaths->url('web_resources') . '"'
            ],
            'import_dirs' => [
                $this->appPaths->absolute('private_theme') . '/src/' => $commonUrl,
                $this->appPaths->absolute('private_theme') . ApplicationPaths::WEBUI .'/' => $commonUrl,
                $this->appPaths->getPrivateDir() . '/' => $commonUrl,
                $this->appPaths->absolute('webui') . '/' => $commonUrl,
                $this->appPaths->absolute('bower_components') . '/' => $commonUrl,
            ],
        ];
    }

    /**
     * @param array $params
     * @param array $custom
     *
     * @return HtmlResourcesUtility
     */
    public function getUtility(array $params, array $custom = [])
    {
        $preprocessorData = $this->getPreprocessorData();

        return new HtmlResourcesUtility(array_merge(
            [
                'cache_dir'      => $this->appPaths->absolute('kernel_cache') . '/vsymfo_document/resources',
                'cache_db_dir'   => $this->appPaths->absolute('kernel_cache') . '/../document/'
                    . $this->appPaths->getThemeName(),
                'cache_refresh'  => $this->env == 'dev',
                'cache_lifetime' => $params['resources_cache_lifetime'],
                'web_dir'        => $this->appPaths->getPrivateDir(),
                'web_url'        => $this->appPaths->getBasePath(),
                'web_cache_dir'  => $this->appPaths->absolute('web_cache') . '/' . $this->appPaths->getThemeName(),
                'web_cache_url'  => $this->appPaths->url('web_cache') . '/' . $this->appPaths->getThemeName(),
                'less_import_dirs' => $preprocessorData['import_dirs'],
                'less_variables'   => $preprocessorData['variables'],
                'scss_import_dirs' => array_keys($preprocessorData['import_dirs']),
                'scss_variables'   => $preprocessorData['variables'],
                'versioning_enable'    => $params['versioning_enable'],
                'versioning_version'   => $params['versioning_version'],
                'versioning_timestamp' => $params['versioning_timestamp'],
                'cdn_enable'     => $params['cdn_enable'],
                'cdn_javascript' => $params['cdn_javascript'],
                'cdn_css'        => $params['cdn_css'],
            ], $custom));
    }

    /**
     * @return JavaScriptResourceManager
     */
    public function getJsLoader()
    {
        $utility = $this->getUtility($this->params, [
            'cache_db_dir'   => $this->appPaths->absolute('kernel_cache') . '/../document_jsloader/loader',
            'web_cache_dir'  => $this->appPaths->absolute('web_cache'),
            'web_cache_url'  => $this->appPaths->url('web_cache'),
        ]);
        $doc = new HtmlDocument();
        $utility->createResOnAdd($doc, "javascript", "default");
        $doc->resources("javascript")->chooseOnAdd("default");
        $locator = new FileLocator($this->appPaths->absolute("kernel_root") . '/document');
        $loader = $utility->createResourcesLoader($doc, 'javascript', $locator, '/');
        $loader->load('jsloader.yml', 'jsloader');

        return $doc->resources("javascript");
    }
}
