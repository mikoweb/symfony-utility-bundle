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

use Symfony\Component\DependencyInjection\ContainerInterface;
use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\Format\PdfDocument;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage DocumentSetup
 */
final class PdfDocumentSetup implements DocumentSetupInterface
{
    /**
     * @var PdfDocument
     */
    private $doc;

    /**
     * @param PdfDocument $doc
     */
    public function __construct(PdfDocument $doc)
    {
        $this->doc = $doc;
    }

    /**
     * Opcje wkhtmltopdf
     * @param ApplicationPaths $path
     * @param string $uri
     * @param string $wkhtmltopdfBin
     * @return array
     */
    public static function wkhtmltopdfOptions(ApplicationPaths $path, $uri, $wkhtmltopdfBin)
    {
        return array(
            "dummy_pdf_url" => $path->url("web") . "/pdf/empty.pdf",
            "display_url" => $uri . "?do=display",
            "download_url" => $uri . "?do=download",
            "remote_url" => $uri . "?do=display",
            "pluginDetect_PDFReader_url" => $path->url("web") . "/pdf/PluginDetect_PDFReader.js",
            "waiting_view_path" => $path->absolute("web") . "/pdf/waiting-view.html",
            "queue_db_path" => $path->absolute("kernel_root") . '/pdf/queue.db',
            "wkhtmltopdf_global" => array(
                "binary" => $wkhtmltopdfBin
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function setup(ContainerInterface $container)
    {
        $params = $container->getParameter('vsymfo_core.document');
        $request = $container->get("request");
        $path = $container->get("app_path");
        $this->doc->setOptions(self::wkhtmltopdfOptions($path, $request->getUri(), $params['pdf_wkhtmltopdf_bin']));
        $this->doc->outputSelector(function() {
            return isset($_GET['do']) ? $_GET['do'] : null;
        });

        $head = $this->doc->element('head');
        $bootstrap = new HtmlElement('link');
        $bootstrap->attr('rel', 'stylesheet');
        $bootstrap->attr('type', 'text/css');
        $bootstrap->attr('href', $container->get('request')->getSchemeAndHttpHost() . $path->url("web") . '/pdf/css/bootstrap.min.css');
        $bootstrap->insertTo($head);

        $style = new HtmlElement('link');
        $style->attr('rel', 'stylesheet');
        $style->attr('type', 'text/css');
        $style->attr('href', $container->get('request')->getSchemeAndHttpHost() . $path->url("web") . '/pdf/css/pdf.css');
        $style->insertTo($head);

        $filename = $path->absolute("kernel_root") . '/pdf' . $request->getPathInfo();
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->doc->setFilename($filename);
    }
}
