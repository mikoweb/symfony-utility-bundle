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

use Symfony\Component\HttpFoundation\RequestStack;
use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\Format\PdfDocument;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class PdfDocumentService implements DocumentFactoryInterface
{
    /**
     * @var ApplicationPaths
     */
    protected $appPaths;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param ApplicationPaths $appPaths
     * @param array $params
     * @param RequestStack $requestStack
     */
    public function __construct(ApplicationPaths $appPaths, array $params, RequestStack $requestStack)
    {
        $this->appPaths = $appPaths;
        $this->params = $params;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function createDocument(array $options = [])
    {
        $document = new PdfDocument();

        $params = $this->params;
        $request = $this->requestStack->getCurrentRequest();
        $document->setOptions($this->getWkHtmlToPdfOptions($request->getUri(), $params['pdf_wkhtmltopdf_bin']));
        $document->outputSelector(function() {
            return isset($_GET['do']) ? $_GET['do'] : null;
        });

        $head = $document->element('head');
        $bootstrap = new HtmlElement('link');
        $bootstrap->attr('rel', 'stylesheet');
        $bootstrap->attr('type', 'text/css');
        $bootstrap->attr('href', $request->getSchemeAndHttpHost() . $this->appPaths->url("web") 
            . '/pdf/css/bootstrap.min.css');
        $bootstrap->insertTo($head);

        $style = new HtmlElement('link');
        $style->attr('rel', 'stylesheet');
        $style->attr('type', 'text/css');
        $style->attr('href', $request->getSchemeAndHttpHost() . $this->appPaths->url("web") . '/pdf/css/pdf.css');
        $style->insertTo($head);

        $filename = $this->appPaths->absolute("kernel_root") . '/../pdf/' . $request->getPathInfo();
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $document->setFilename($filename);

        return $document;
    }

    /**
     * Returns wkhtmltopdf options.
     *
     * @param string $uri
     * @param string $wkHtmlToPdfBin
     *
     * @return array
     */
    public function getWkHtmlToPdfOptions($uri, $wkHtmlToPdfBin)
    {
        return [
            'dummy_pdf_url' => $this->appPaths->url('web') . '/pdf/empty.pdf',
            'display_url' => $uri . '?do=display',
            'download_url' => $uri . '?do=download',
            'remote_url' => $uri . '?do=display',
            'pluginDetect_PDFReader_url' => $this->appPaths->url('web') . '/pdf/PluginDetect_PDFReader.js',
            'waiting_view_path' => $this->appPaths->absolute('web') . '/pdf/waiting-view.html',
            'queue_db_path' => $this->appPaths->absolute('kernel_root') . '/pdf/queue.db',
            'wkhtmltopdf_global' => [
                'binary' => $wkHtmlToPdfBin
            ]
        ];
    }
}
