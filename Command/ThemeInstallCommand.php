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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Command
 */
class ThemeInstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('vsymfo:theme:install')
            ->setDescription('Install specific theme.')
            ->addArgument('node_package', InputArgument::REQUIRED, 'Node package name.')
            ->addArgument('theme', InputArgument::REQUIRED, 'Theme name.')
            ->addOption('force', false, InputOption::VALUE_NONE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $this->getContainer()->get('app_path');
        $package = $input->getArgument('node_package');
        $theme = $input->getArgument('theme');
        $force = $input->getOption('force');

        $packagePath = $paths->absolute('node_modules') . '/' . $package;
        $fs = new Filesystem();

        if (!$fs->exists($packagePath)) {
            throw new FileNotFoundException("Package \"$package\" not found.");
        }

        $privatePath = $paths->getPrivateDir() . ApplicationPaths::WEB_THEMES . '/' . $theme;
        $this->copyDir($packagePath, $privatePath, $force);
        $webPath = $paths->getWebDir() . ApplicationPaths::WEB_THEMES . '/' . $theme;

        if ($fs->exists($privatePath . '/public')) {
            $this->copyDir($privatePath . '/public', $webPath, $force);
            $fs->remove($privatePath . '/public');
        }

        $extraPath = $paths->getPrivateDir() . '/theme_extra/' . $theme . '/public';

        if ($fs->exists($extraPath)) {
            $fs->mirror($extraPath, $webPath);
        }

        $output->writeln('<fg=black;bg=green>Theme "' . $theme . '" has been installed.</>');
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $force
     */
    protected function copyDir($from, $to, $force)
    {
        $fs = new Filesystem();
        $exist = $fs->exists($to);

        if ($exist && $force) {
            $fs->remove($to);
            $fs->mirror($from, $to);
        } elseif ($exist && !$force) {
            throw new \UnexpectedValueException('Already exists. Use --force option.');
        } else {
            $fs->mirror($from, $to);
        }
    }
}
