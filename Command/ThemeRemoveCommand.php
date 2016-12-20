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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Command
 */
class ThemeRemoveCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('vsymfo:theme:remove')
            ->setDescription('Remove specific theme.')
            ->addArgument('theme', InputArgument::REQUIRED, 'Theme name.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $this->getContainer()->get('app_path');
        $theme = $input->getArgument('theme');
        $items = 0;

        if ($this->removeDir($output, $paths->getPrivateDir() . ApplicationPaths::WEB_THEMES . '/' . $theme)) {
            $items++;
        }

        if ($this->removeDir($output, $paths->getWebDir() . ApplicationPaths::WEB_THEMES . '/' . $theme)) {
            $items++;
        }

        if ($items > 0) {
            $output->writeln('<fg=black;bg=green>Theme "' . $theme . '" has been removed.</>');
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $dir
     *
     * @return bool
     */
    protected function removeDir(OutputInterface $output, $dir)
    {
        $fs = new Filesystem();
        $removed = false;

        if ($fs->exists($dir)) {
            $fs->remove($dir);
            $removed = true;
        } else {
            $output->writeln("<error>Not found directory: $dir</error>");
        }

        return $removed;
    }
}
