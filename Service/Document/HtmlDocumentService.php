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
use vSymfo\Component\Document\CssPreprocessor\NodeSassPreprocessor;
use vSymfo\Component\Document\FileLoader\TranslationLoader;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\Interfaces\CombineResourceInterface;
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
     * @var \Twig_Loader_Filesystem
     */
    protected $twigLoader;

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
     * @param \Twig_Loader_Filesystem $twigLoader
     * @param ActiveTheme $theme
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ApplicationPaths $appPaths,
        $env,
        array $params,
        RequestStack $requestStack,
        \Twig_Environment $twig,
        \Twig_Loader_Filesystem $twigLoader,
        ActiveTheme $theme,
        TranslatorInterface $translator
    ) {
        $this->appPaths = $appPaths;
        $this->env = $env;
        $this->params = $params;
        $this->requestStack = $requestStack;
        $this->twig = $twig;
        $this->twigLoader = $twigLoader;
        $this->theme = $theme;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function createDocument(array $options = [])
    {
        $document = new HtmlDocument();

        if (isset($this->params['node_sass_run_mode']) && is_string($this->params['node_sass_run_mode'])
            && !empty($this->params['node_sass_run_mode']))
        {
            NodeSassPreprocessor::setRunMode($this->params['node_sass_run_mode']);
        }

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
        if ($this->params["favicon_enable"]) {
            $favicon->enable(true);
            $favicon->setBasePath($this->appPaths->url('web_theme') . '/favicons');
            $favicon->setTileColor($this->params["favicon_tile_color"]);
            if (file_exists($this->appPaths->absolute('web_theme') . '/favicons/favicon.html')) {
                $favicon->setFaviconTemplate(file_get_contents($this->appPaths->absolute('web_theme') . '/favicons/favicon.html'));
            }
        } else {
            $favicon->enable(false);
        }

        // html lang
        $document->element('html')->attr('lang', $this->translator->getLocale());

        // resources
        if ($this->twigLoader->exists('::before-stylesheets.html.twig')) {
            $document->beforeStyleSheets($this->twig->render('::before-stylesheets.html.twig'));
        }

        if ($this->twigLoader->exists('::after-stylesheets.html.twig')) {
            $document->afterStyleSheets($this->twig->render('::after-stylesheets.html.twig'));
        }

        $utility = $this->getUtility($this->params);
        $utility->createResOnAdd($document, "javascript", "default");
        $utility->createResOnAdd($document, "stylesheet", "default");

        $document->resources("javascript")->chooseOnAdd("default");
        $document->resources("stylesheet")->chooseOnAdd("default");

        $configDir = $this->appPaths->getRootDir() . '/document';

        if (file_exists($configDir . '/' . $this->theme->getName())) {
            $configDir .= '/' . $this->theme->getName();
        }

        $locator = new FileLocator($configDir);
        $loader = $utility->createResourcesLoader($document, 'javascript', $locator, '/');
        $loader->load('html_resources.yml', 'framework');
        $loader->load('html_resources.yml', 'core');

        $locator = new FileLocator($this->appPaths->absolute('private_theme'));
        $loader = $utility->createResourcesLoader($document, 'stylesheet', $locator, $this->appPaths->getThemePath() . '/src');
        $loader->load('html_resources.yml', 'theme');
        $loader = $utility->createResourcesLoader($document, 'javascript', $locator, $this->appPaths->getThemePath() . '/src');
        $loader->load('html_resources.yml', 'theme');

        if (!in_array('jsloader', $this->params['disabled'], true) ||
            in_array($this->theme->getName(), $this->params['jsloader_themes'], true)
        ) {
            $document->setScriptsLocation(HtmlDocument::SCRIPTS_LOCATION_TOP);
            // js initializer
            $jsloader = $this->getJsLoader();
            $script = $document->element('script');
            $twig = $this->twig;
            $env = $this->env;
            $document->setScriptOutput(function (JavaScriptResourceManager $manager, array $translations)
            use($jsloader, $script, $twig, $env)
            {
                $output = $jsloader->render('html');

                if ($env === 'dev') {
                    foreach ($jsloader->resources() as $resource) {
                        if ($resource instanceof CombineResourceInterface
                            && $resource->getCombineObject()->getException() instanceof \Exception
                        ) {
                            throw $resource->getCombineObject()->getException();
                        }
                    }
                }

                $output .= $twig->render('::head.html.twig', [
                    "resources" => $manager->render('array'),
                    "translations" => $translations,
                ]);

                if (!$script->isEmpty()) {
                    $output .= '<script type="text/javascript">' . $script->render() . '</script>';
                    $script->update(function ()  {
                        return '';
                    });
                }

                return $output;
            });
        } else {
            $document->setScriptsLocation(HtmlDocument::SCRIPTS_LOCATION_NONE);
        }

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
        $themePath = $this->appPaths->absolute('private_theme');
        $themeExtraPath = preg_replace('/^(.*)(' . str_replace('/', '\/', ApplicationPaths::WEB_THEMES) . '\/)([^\/]+)$/',
            '${1}' . ApplicationPaths::WEB_THEMES . '_extra/${3}', $themePath);

        return [
            'variables' => [
                'path-base'      => '"' . $this->appPaths->url('base') . '"',
                'path-theme'     => '"' . $this->appPaths->url('web_theme') . '"',
                'path-resources' => '"' . $this->appPaths->url('web_resources') . '"'
            ],
            'import_dirs' => [
                $themeExtraPath . '/src/' => $commonUrl,
                $themeExtraPath . ApplicationPaths::WEBUI .'/' => $commonUrl,
                $themePath . '/extra/' => $commonUrl,
                $themePath . '/src/' => $commonUrl,
                $themePath . ApplicationPaths::WEBUI .'/' => $commonUrl,
                $this->appPaths->getPrivateDir() . '/' => $commonUrl,
                $this->appPaths->absolute('webui') . '/' => $commonUrl,
                $this->appPaths->absolute('node_modules') . '/' => $commonUrl,
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
