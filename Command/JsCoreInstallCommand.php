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
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Command
 */
class JsCoreInstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('vsymfo:js-core:install')
            ->setDescription('Install core JavaScripts.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $this->getContainer()->get('app_path');
        $fs = new Filesystem();
        $packageSource = $paths->absolute('node_modules') . '/vsymfo-js-core/src';
        $targetDir = $paths->getPrivateDir() . '/src';

        if (!$fs->exists($packageSource)) {
            throw new FileNotFoundException("Source files not found.");
        }

        $toRemove = [
            $targetDir . '/vsymfo',
            $targetDir . '/.babelrc',
            $targetDir . '/.eslintrc',
            $targetDir . '/Gruntfile.js',
            $targetDir . '/package.json',
        ];

        foreach ($toRemove as $path) {
            if ($fs->exists($path)) {
                $fs->remove($path);
            }
        }

        $fs->mirror($packageSource, $targetDir);

        $output->writeln('<fg=black;bg=green>Core JavaScripts installation successful.</>');
    }
}
