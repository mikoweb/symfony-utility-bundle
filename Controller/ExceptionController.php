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

use Symfony\Bundle\TwigBundle\Controller\ExceptionController as EC;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use vSymfo\Bundle\CoreBundle\EventListener\DocumentListener;

/**
 * Kontroler wyjątków przystosowany do vSymfo Document
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Controller
 */
class ExceptionController extends EC
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function __construct(\Twig_Environment $twig, $debug, ContainerInterface $container)
    {
        parent::__construct($twig, $debug);
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        if ($request->get('format') === 'html' && $this->container->get('kernel')->getEnvironment() === 'prod') {
            // trzeba utworzyć dodatkową usługę dokumentu
            $docListener = new DocumentListener($this->container, 'html', 'exception_document');
            $event = new GetResponseEvent($this->container->get('kernel'), $request, HttpKernelInterface::MASTER_REQUEST);
            $docListener->onKernelRequest($event);

            $response = parent::showAction($request, $exception, $logger);
            $this->container->get('exception_document')->body($response->getContent());
            $response->setContent($this->container->get('exception_document')->render());

            return $response;
        }

        return parent::showAction($request, $exception, $logger);
    }
}
