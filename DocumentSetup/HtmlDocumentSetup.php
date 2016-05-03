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

namespace vSymfo\Bundle\CoreBundle\DocumentSetup;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\FileLoader\TranslationLoader;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\JavaScriptResource;
use vSymfo\Component\Document\Resources\JavaScriptResourceManager;
use vSymfo\Component\Document\Utility\HtmlResourcesUtility;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage DocumentSetup
 */
final class HtmlDocumentSetup implements DocumentSetupInterface
{
    /**
     * @var HtmlDocument
     */
    private $doc;

    /**
     * @param HtmlDocument $doc
     */
    public function __construct(HtmlDocument $doc)
    {
        $this->doc = $doc;
    }

    /**
     * @param ApplicationPaths $path
     *
     * @return array
     */
    public static function getPreprocessorData(ApplicationPaths $path)
    {
        return [
            'variables' => [
                'path-base'         => '"' . $path->url('base') . '"',
                'path-theme'        => '"' . $path->url('web_theme') . '"',
                'path-resources'    => '"' . $path->url('web_resources') . '"',
                'path-webui'        => '"' . $path->url('webui') . '"',
                'path-webui-engine' => '"' . $path->url('webui_engine') . '"',
            ],
            'import_dirs' => [
                $path->absolute("web_theme") . $path::WEB_RESOURCES  . '/' => $path->url('web_theme')  . '/',
                $path->absolute("web_resources") . '/' => $path->url('web_theme')  . '/',
                $path->absolute("web") . '/' => $path->url('web_theme')  . '/',
            ],
        ];
    }

    /**
     * @param ApplicationPaths $path
     * @param string $env
     * @param array $params
     * @param array $custom
     *
     * @return HtmlResourcesUtility
     */
    public static function getUtility(ApplicationPaths $path, $env, array $params, array $custom = array())
    {
        $preprocessorData = self::getPreprocessorData($path);

        return new HtmlResourcesUtility(array_merge(
            array(
                'cache_dir'      => $path->absolute("kernel_cache") . "/vsymfo_document/resources",
                'cache_db_dir'   => $path->absolute("kernel_cache") . '/../document/' . $path->getThemeName(),
                'cache_refresh'  => $env == "dev",
                'cache_lifetime' => $params["resources_cache_lifetime"],
                'web_dir'        => $path->absolute("web"),
                'web_url'        => $path->url("web"),
                'web_cache_dir'  => $path->absolute("web_cache") . "/" . $path->getThemeName(),
                'web_cache_url'  => $path->url("web_cache") . "/" . $path->getThemeName(),
                'less_import_dirs' => $preprocessorData['import_dirs'],
                'less_variables'   => $preprocessorData['variables'],
                'scss_import_dirs' => array_keys($preprocessorData['import_dirs']),
                'scss_variables'   => $preprocessorData['variables'],
                'versioning_enable'    => $params["versioning_enable"],
                'versioning_version'   => $params["versioning_version"],
                'versioning_timestamp' => $params["versioning_timestamp"],
                'cdn_enable'     => $params["cdn_enable"],
                'cdn_javascript' => $params["cdn_javascript"],
                'cdn_css'        => $params["cdn_css"],
            ), $custom));
    }

    /**
     * @inheritdoc
     */
    public function setup(ContainerInterface $container)
    {
        $params = $container->getParameter("vsymfo_core.document");
        $path = $container->get('app_path');
        $head = $this->doc->element('head');

        // klasa no-js w html
        $this->doc->element('html')->addClass('no-js');

        // meta viewport
        $viewport = $params["meta_viewport"];
        if (!empty($viewport)) {
            $meta = $this->doc->element('viewport');
            $meta->attr('content', $viewport);
        }

        // favicon
        $favicon = $this->doc->element('favicon');
        $favicon->enable($params["favicon_enable"]);
        $favicon->setBasePath($path->url('web_theme') . '/favicons');
        $favicon->setTileColor($params["favicon_tile_color"]);
        if (file_exists($path->absolute('web_theme') . '/favicons/favicon.html')) {
            $favicon->setFaviconTemplate(file_get_contents($path->absolute('web_theme') . '/favicons/favicon.html'));
        }

        // html lang
        $this->doc->element('html')->attr('lang', $container->get('request')->getLocale());

        // znacznik base
        /* Może powodować problemy w linkach z kratką
         * $base = new HtmlElement('base');
        if ($container->get('kernel')->getEnvironment() == "dev")
            $base->attr('href', $path->url('base') . '/app_dev.php/');
        else
            $base->attr('href', $path->url('base') . '/');
        $base->insertTo($head);*/

        // zasoby js i css
        $utility = self::getUtility($path, $container->get('kernel')->getEnvironment(), $params);
        $utility->createResOnAdd($this->doc, "javascript", "default");
        $utility->createResOnAdd($this->doc, "stylesheet", "default");

        $this->doc->resources("javascript")->chooseOnAdd("default");
        $this->doc->resources("stylesheet")->chooseOnAdd("default");

        $locator = new FileLocator($path->absolute("kernel_root") . '/document');
        // generowanie kolekcji zasobów systemowych
        $loader = $utility->createResourcesLoader($this->doc, 'javascript', $locator, $path->url('web_resources', false));
        $loader->load('html_resources.yml', 'framework');
        $loader->load('html_resources.yml', 'core');

        // generowanie kolekcji zasobów motywu
        $locator = new FileLocator($path->absolute("kernel_root") . '/theme/' . $path->getThemeName());
        $loader = $utility->createResourcesLoader($this->doc, 'stylesheet', $locator, $path->url("web_theme", false) . $path::WEB_RESOURCES);
        $loader->load('html_resources.yml', 'theme');
        $loader = $utility->createResourcesLoader($this->doc, 'javascript', $locator, $path->url("web_theme", false) . $path::WEB_RESOURCES);
        $loader->load('html_resources.yml', 'theme');

        // kopiujemy manager'a js po to żeby utworzyć jeden plik
        $dir = $path->url('web_resources', false);
        $jsloader = clone $this->doc->resources("javascript");
        $jsloader->getGroups()->addGroup("jsloader");
        $jsloader->add(
            new JavaScriptResource('',
                array(
                    $dir . '/engine/js/modernizr/modernizr.custom.js',
                    $dir . '/engine/js/modernizr/modernizr.es5.js',
                    $dir . '/engine/js/modernizr/isarray.js',
                    $dir . '/engine/js/modernizr/yepnope.js',
                    $dir . '/engine/js/core/jsloader/jsloader.js',
                    $dir . '/fosjsrouting.js',
                    $dir . '/startapp.js'
                ),
                array('combine' => true)
            ), 'jsloader'
        );

        // loader javascript
        $script = $this->doc->element('script');
        $twig = $container->get('twig');
        $jsloaderPath = $jsloader->render('array');
        $jsloaderPath = $jsloaderPath['resources']['jsloader'][0]['url'][0];
        $this->doc->setScriptOutput(function (JavaScriptResourceManager $manager, array $translations) use($container, $jsloaderPath, $params, $script, $twig, $path) {
            $output = '<script src="' . $jsloaderPath . '" type="text/javascript"></script>';
            $output .= '<script type="text/javascript">';
            $output .= $twig->render('::head.js.twig', array(
                "resources" => $manager->render('array'),
                "translations" => $translations,
                "timeout" => (int)$params['resources_loading_timeout'],
                "css_callback_timeout" => (int)$params['css_callback_timeout'],
                "theme_name" => $container->get('liip_theme.active_theme')->getName(),
                "path" => array(
                    "base" => $path->url("base"),
                    "theme" => $path->url("web_theme"),
                    "webui" => $path->url("webui"),
                    "webui_engine" => $path->url("webui_engine"),
                    "resources" => $path->url("web_resources"),
                    "cdn_javascript" => $params['cdn_enable'] ? $params['cdn_javascript'] : '',
                    "cdn_css" => $params['cdn_enable'] ? $params['cdn_css'] : '',
                    "cdn_image" => $params['cdn_enable'] ? $params['cdn_image'] : '',
                )
            ));
            $output .= '</script>';
            if (!$script->isEmpty()) {
                $output .= '<script type="text/javascript">' . $script->render() . '</script>';
                $script->update(function ()  {
                    return '';
                });
            }
            return $output;
        });

        // tłumaczenia używane w motywie graficznym
        if (file_exists($path->absolute("kernel_root") . '/theme/' . $path->getThemeName() . '/translations.yml')) {
            $trans = $container->get('translator');
            $locator = new FileLocator($path->absolute("kernel_root") . '/theme/' . $path->getThemeName());
            $loader = new TranslationLoader($locator, array(
                'cache_dir' => $path->absolute("kernel_cache") . "/vsymfo_document/translations",
                'cache_refresh' => $container->get('kernel')->getEnvironment() == "dev",
                'document' => $this->doc,
                'trans_closure' => function ($text) use($trans) {
                    return $trans->trans($text);
                }
            ));
            $loader->load('translations.yml');
        }
    }
}
