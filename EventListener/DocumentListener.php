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

namespace vSymfo\Bundle\CoreBundle\EventListener;

use ReflectionClass;
use vSymfo\Bundle\CoreBundle\DocumentSetup;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use vSymfo\Component\Document\Format;

/**
 * Tworzenie usługi dokumentu
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage EventListener
 */
class DocumentListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Router
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $container->get('router');
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $collection = $this->router->getRouteCollection();
        $route = $collection->get($request->get('_route'));
        if (empty($route)) {
            $route = $collection->get($request->get('_locale'). '__RG__'. $request->get('_route'));
        }

        if (!empty($route))  {
            $defaultFormat = is_null($route->getDefault('_format')) ? 'html' : $route->getDefault('_format');
            $format = !is_null($request->get('_format')) ? $request->get('_format') : $defaultFormat;
            switch ($format) {
                case 'html':
                    $doc = new Format\HtmlDocument();
                    $setup = new DocumentSetup\HtmlDocumentSetup($doc);
                    $setup->setup($this->container);
                    break;
                case 'xml':
                    $doc = new Format\XmlDocument();
                    break;
                case 'pdf':
                    $doc = new Format\PdfDocument();
                    $setup = new DocumentSetup\PdfDocumentSetup($doc);
                    $setup->setup($this->container);
                    break;
                case 'rss':
                    $doc = new Format\RssDocument();
                    break;
                case 'atom':
                    $doc = new Format\AtomDocument();
                    break;
                case 'txt':
                default:
                    $doc = new Format\TxtDocument();
            }

            $params = $this->container->getParameter("app_core.document");
            $r = new ReflectionClass('vSymfo\Component\Document\Format\DocumentAbstract');
            $mode = $r->getConstant("TITLE_" . strtoupper($params["title_mode"]));
            $doc->name($params["sitename"]);
            $doc->title($params["title_default"], $mode, $params["title_separator"]);
            $doc->keywords($params["keywords"]);
            $doc->description($params["description"]);
            $this->container->set('document', $doc);
        }
    }
}
