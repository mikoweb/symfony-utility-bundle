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
class JsBuildCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('vsymfo:js:build')
            ->setDescription('Build core JavaScripts.')
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

        if ($fs->exists($paths->getPrivateDir() . '/js')) {
            $fs->remove($paths->getPrivateDir() . '/js');
        }

        $process = new Process(
            'cd "' . $paths->getPrivateDir() . '/src" && ' .
            (
                $env === 'prod'
                ? 'npm install && npm run dist'
                : 'npm install && npm run dev-dist'
            )
        );
        $process->start();

        $process->wait(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });

        if ($process->getExitCode() === 0) {
            $output->writeln('<fg=black;bg=green>Build successful.</>');
        } else {
            $output->writeln('<error>Exit code: ' . $process->getExitCode() . ' Message: '
                . $process->getExitCodeText() . '</error>');
        }
    }
}
