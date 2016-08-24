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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use vSymfo\Bundle\CoreBundle\DocumentSetup\HtmlDocumentSetup;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\StyleSheetResource;
use vSymfo\Core\ApplicationPaths;
use vSymfo\Core\Controller;

/**
 * Resources controller i.a. for WebUI.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Controller
 */
class ResourcesController extends Controller
{
    /**
     * Gets StyleSheet for WebUI module.
     *
     * @param Request $request
     * @param $name Module name.
     * @param $theme Theme name.
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function webuiCssAction(Request $request, $name, $theme)
    {
        try {
            $this->get('liip_theme.active_theme')->setName($theme);
        } catch (\Exception $e) {
            throw new NotFoundHttpException('Theme not found');
        }

        if (strpos($name, '.') !== false) {
            throw new NotFoundHttpException('Invalid name');
        }

        $name = str_replace('|', '/', $name);
        $doc = new HtmlDocument();
        $params = $this->getParameter("vsymfo_core.document");
        $path = $this->get('app_path');
        $documentService = $this->get('vsymfo_core.service.html_document');

        $preprocessorData = $documentService->getPreprocessorData();
        $preprocessorImportVariables = $preprocessorData['variables'];
        $preprocessorImportDirs = [
            $path->absolute("web_theme") . ApplicationPaths::WEB_WEBUI . '/' => $path->url('web_theme')  . '/',
            $path->absolute("webui") . '/' => $path->url('web_theme')  . '/',
            $path->absolute("web_theme") . ApplicationPaths::WEB_RESOURCES  . '/' => $path->url('web_theme')  . '/',
            $path->absolute("web_resources") . '/' => $path->url('web_theme')  . '/',
            $path->absolute("web") . '/' => $path->url('web_theme')  . '/',
        ];

        $utility = $documentService->getUtility($params, [
            'less_import_dirs' => $preprocessorImportDirs,
            'less_variables'   => $preprocessorImportVariables,
            'scss_import_dirs' => array_keys($preprocessorImportDirs),
            'scss_variables'   => $preprocessorImportVariables,
        ]);

        $utility->createResOnAdd($doc, 'stylesheet', 'default');
        $doc->resources('stylesheet')->chooseOnAdd('default');

        $manager = $doc->resources('stylesheet');
        $manager->getGroups()->addGroup('webui');

        $manager->add(
            new StyleSheetResource(str_replace('/', '__', $name),
                array($this->cssFilename($name)),
                array('combine' => true)
            ), 'webui'
        );

        $manager->render('html');
        $res = $manager->resources();
        $source = $res[0]->getCombineObject()->getPath();

        $response = new Response();
        $date = new \DateTime();
        $date->setTimestamp(filemtime($source));
        $response->setLastModified($date);
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent(file_get_contents($source));
        $response->headers->set('Content-Type', 'text/css');

        return $response;
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws NotFoundHttpException
     */
    private function cssFilename($name)
    {
        $path = $this->get('app_path');
        $nameInfo = pathinfo($name);
        $basePath = null;
        $baseUrl = null;

        if (file_exists($path->absolute('web_theme') . ApplicationPaths::WEB_WEBUI . '/' . $name . '.json')) {
            $basePath = $path->absolute('web_theme') . ApplicationPaths::WEB_WEBUI . '/' . $nameInfo['dirname'];
            $baseUrl = $path->url('web_theme', false)  . ApplicationPaths::WEB_WEBUI . '/' . $nameInfo['dirname'];
        } elseif (file_exists($path->absolute('webui') . '/' . $name . '.json')) {
            $basePath = $path->absolute('webui') . '/' . $nameInfo['dirname'];
            $baseUrl = $path->url('webui', false) . '/' . $nameInfo['dirname'];
        }

        if (!$basePath) {
            throw new NotFoundHttpException('Package not found');
        }

        $baseName = $basePath . '/' . $nameInfo['basename'];
        $json = json_decode(file_get_contents($baseName . '.json'), true);

        if (!isset($json['main_css'])) {
            throw new NotFoundHttpException('Main css not found');
        }

        if (strpos($json['main_css'], '..') !== false) {
            throw new NotFoundHttpException('Invalid main css name');
        }

        $mainInfo = pathinfo($json['main_css']);
        if (!in_array($mainInfo['extension'], ['scss', 'less', 'css'])) {
            throw new NotFoundHttpException('Invalid main css extension');
        }

        return $baseUrl . '/' . $json['main_css'];
    }
}
