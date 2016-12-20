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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Command
 */
class JsInstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('vsymfo:js:install')
            ->setDescription('Install core JavaScripts.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $this->getContainer()->get('app_path');
        $env = $input->getOption('env');
        $fs = new Filesystem();
        $webPath = $paths->getWebDir() . '/js';
        $privatePath = $paths->getPrivateDir() . '/js';

        if ($fs->exists($privatePath)) {
            if ($fs->exists($webPath)) {
                $fs->remove($webPath);
            }

            if ($env === 'prod') {
                $fs->mirror($privatePath, $webPath);
            } else {
                $fs->symlink($privatePath, $webPath);
            }
        }

        $output->writeln('<fg=black;bg=green>JavaScripts installation successful.</>');
    }
}
