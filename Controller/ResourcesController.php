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

namespace vSymfo\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use vSymfo\Bundle\CoreBundle\DocumentSetup\HtmlDocumentSetup;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\StyleSheetResource;
use vSymfo\Core\Controller;

/**
 * Kontroler zasobów dla WebUI
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Controller
 */
class ResourcesController extends Controller
{
    /**
     * Arkusz CSS dla określonego modułu WebUI
     * @param $name nazwa modułu
     * @param $theme nazwa motywu graficznego
     * @return Response
     * @throws NotFoundHttpException
     */
    public function webuiCssAction($name, $theme)
    {
        try {
            $this->get('liip_theme.active_theme')->setName($theme);
        } catch (\Exception $e) {
            throw new NotFoundHttpException('Theme not found.');
        }

        $name = str_replace('|', '/', $name);
        $doc = new HtmlDocument();
        $params = $this->container->getParameter("vsymfo_core.document");
        $path = $this->container->get('app_path');
        $utility = HtmlDocumentSetup::getUtility($path, $this->container->get('kernel')->getEnvironment(), $params, array(
            'less_import_dirs' => array(
                $path->absolute("web_theme") . $path::WEB_WEBUI . '/' => $path->url('web_theme')  . '/',
                $path->absolute("webui") . '/' => $path->url('web_theme')  . '/',
                $path->absolute("web_theme") . $path::WEB_RESOURCES  . '/' => $path->url('web_theme')  . '/',
                $path->absolute("web_resources") . '/' => $path->url('web_theme')  . '/',
            ),
            'less_globasls' => array(
                'path-base'      => '"' . $path->url('base') . '"',
                'path-theme'     => '"' . $path->url('web_theme') . '"',
                'path-resources' => '"' . $path->url('web_resources') . '"',
                'path-webui'     => '"' . $path->url('webui') . '"',
                'path-webui-engine' => '"' . $path->url('webui_engine') . '"',
                'domain-path' => '""',
            ),
        ));

        $utility->createResOnAdd($doc, "stylesheet", "default");
        $doc->resources("stylesheet")->chooseOnAdd("default");

        $manager = $doc->resources("stylesheet");
        $manager->getGroups()->addGroup("webui");

        $filename = false;
        if (file_exists($path->absolute("web_theme") . $path::WEB_WEBUI . '/' . $name . '.less')) {
            $filename =  $path->url("web_theme", false)  . $path::WEB_WEBUI . '/' . $name . '.less';
        } elseif (file_exists($path->absolute("webui") . '/' . $name . '.less')) {
            $filename =  $path->url("webui", false) . '/' . $name . '.less';
        }

        if ($filename) {
            $manager->add(
                new StyleSheetResource(str_replace('/', '__', $name),
                    array($filename),
                    array('combine' => true)
                ), 'webui'
            );
        }

        if ($manager->length()) {
            $manager->render('html');
            $res = $manager->resources();
            $source = $res[0]->getCombineObject()->getPath();
            $response = new Response();
            $response->setContent(file_get_contents($source));
            $response->headers->set('Content-Type', 'text/css');

            return $response;
        }

        throw new NotFoundHttpException('Package not found!');
    }
}
