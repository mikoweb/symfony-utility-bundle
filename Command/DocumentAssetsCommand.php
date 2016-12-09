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

namespace vSymfo\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use vSymfo\Bundle\CoreBundle\Service\Document\HtmlDocumentService;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\Interfaces\CombineResourceInterface;
use vSymfo\Component\Document\Resources\Interfaces\ResourceInterface;
use vSymfo\Component\Document\Resources\Interfaces\ResourceManagerInterface;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Command
 */
class DocumentAssetsCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('document:assets')
            ->setDescription('Compile document assets.')
            ->addOption(
                'base_path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Base path of your site',
                '/'
            )
            ->addOption('theme', null, InputOption::VALUE_OPTIONAL)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('kernel')->getEnvironment();
        $this->output = $output;
        $this->setFakeRequest($input->getOption('base_path'));
        $htmlDocumentService = $this->createHtmlDocumentService();
        $this->clearCache();
        $output->writeln('<info>Documents cache has been cleared</info>');
        $this->generatingJsLoader($htmlDocumentService);
        
        $theme = $input->getOption('theme');

        if (is_null($theme)) {
            foreach ($this->getThemes() as $theme) {
                $this->generatingForTheme($htmlDocumentService, $theme);
            }
        } else {
            $this->generatingForTheme($htmlDocumentService, $theme);
        }
    }

    /**
     * @param string $basePath
     *
     * @return Request
     */
    protected function setFakeRequest($basePath)
    {
        $stack = $this->getContainer()->get('request_stack');
        $request = new Request();
        $request->server->set('SCRIPT_FILENAME', '/app.php');
        $request->server->set('PHP_SELF', $basePath . '/app.php');
        $request->headers->set('X_ORIGINAL_URL', $basePath . '/app.php');
        $stack->push($request);

        return $request;
    }

    /**
     * @return array
     */
    protected function getThemes()
    {
        return $this->getContainer()->getParameter('liip_theme.themes');
    }

    /**
     * @return HtmlDocumentService
     */
    protected function createHtmlDocumentService()
    {
        $container = $this->getContainer();
        $params = $container->getParameter('vsymfo_core.document');
        $params['resources_cache_lifetime'] = 1;

        return new HtmlDocumentService(
            $container->get('app_path'),
            'dev',
            $params,
            $container->get('request_stack'),
            $container->get('twig'),
            $container->get('twig.loader'),
            $container->get('liip_theme.active_theme'),
            $container->get('translator')
        );
    }

    /**
     * @param HtmlDocumentService $service
     *
     * @return ResourceManagerInterface
     */
    protected function generatingJsLoader(HtmlDocumentService $service)
    {
        $resources = $service->getJsLoader();
        $resources->render('html');
        $this->output->writeln('Generating JsLoader');
        $this->outputResources($resources);
        foreach ($resources->resources() as $resource) {
            $this->checkResourceProblems($resource);
        }
        $this->output->writeln('');

        return $resources;
    }

    /**
     * @param HtmlDocumentService $service
     * @param string $theme
     *
     * @return HtmlDocument
     */
    protected function generatingForTheme(HtmlDocumentService $service, $theme)
    {
        $this->getContainer()->get('liip_theme.active_theme')->setName($theme);
        $this->output->writeln('Generating for theme <info>' . $theme . '</info>');
        $document = $service->createDocument();
        $document->render();

        $this->output->writeln('');
        $this->output->writeln('<comment>Generated JavaScripts:</comment>');
        $this->outputResources($document->resources('javascript'));
        $this->output->writeln('<comment>Generated StyleSheets:</comment>');
        $this->outputResources($document->resources('stylesheet'));

        $this->output->writeln('<comment>Generated WebUI styles:</comment>');
        $request = $this->getContainer()->get('request_stack')->getCurrentRequest();
        $resourcesController = $this->getContainer()->get('vsymfo_core.controller.resources');
        $paths = $this->getWebuiStylePaths();
        foreach ($paths as $path) {
            $this->output->writeln('<fg=black;bg=green>' . $path . '</>');
            try {
                $resourcesController->webuiCssAction($request, str_replace('/', '|', $path), $theme);
            } catch (\Exception $e) {
                $this->output->writeln($this->exceptionFormatBlock($e));
            }
        }

        $this->output->writeln('');

        return $document;
    }

    /**
     * @param ResourceManagerInterface $resources
     */
    protected function outputResources(ResourceManagerInterface $resources)
    {
        foreach ($resources->resources() as $resource) {
            $this->checkResourceProblems($resource);
            foreach ($resource->getUrl() as $url) {
                $this->output->writeln('<fg=black;bg=green>' . $url . '</>');
            }
        }
    }

    protected function clearCache()
    {
        $paths = $this->getContainer()->get('app_path');
        $fs = new Filesystem();
        $fs->remove($paths->absolute('web_cache'));
        $fs->remove($paths->getCacheDir() . '/../document');
        $fs->remove($paths->getCacheDir() . '/../document_jsloader');
    }

    /**
     * @param ResourceInterface $resource
     */
    protected function checkResourceProblems(ResourceInterface $resource)
    {
        if ($resource instanceof CombineResourceInterface) {
            if (empty(trim(file_get_contents($resource->getCombineObject()->getPath())))) {
                $formatter = $this->getHelper('formatter');
                $this->output->writeln($formatter->formatBlock(['Warning: compiled file is empty.'], 'error'));
            }

            if ($resource->getCombineObject()->getException() instanceof \Exception) {
                $this->output->writeln($this->exceptionFormatBlock($resource->getCombineObject()->getException()));
            }
        }
    }

    /**
     * @param \Exception $exception
     *
     * @return string
     */
    protected function exceptionFormatBlock(\Exception $exception)
    {
        $formatter = $this->getHelper('formatter');

        return $formatter->formatBlock([
            '[' . get_class($exception) . ']',
            $exception->getMessage(),
            'File: ' . $exception->getFile(),
            'Line: ' . $exception->getLine()
        ], 'error');
    }

    /**
     * @return array
     */
    protected function getWebuiStylePaths()
    {
        $paths = $this->getContainer()->get('app_path');
        $names = [];
        $this->addWebuiStylePaths($names, $paths->absolute('webui') . '/webui', 'style');
        $this->addWebuiStylePaths($names, $paths->absolute('private_theme') . ApplicationPaths::WEBUI, 'style');

        return $names;
    }

    /**
     * @param array $names
     * @param string $path
     * @param string $folder
     */
    protected function addWebuiStylePaths(array &$names, $path, $folder)
    {
        $dir = $path . '/' . $folder;
        if (file_exists($dir)) {
            foreach (new \DirectoryIterator($dir) as $file) {
                $name = '/' . $file->getBasename() . '/' . $file->getBasename();
                if ($file->isDir() && !$file->isDot() && file_exists($file->getPath() . $name . '.json')) {
                    $name = $folder . $name;
                    if (!in_array($name, $names, true)) {
                        $names[] = $name;
                    }
                }
            }
        }
    }
}
