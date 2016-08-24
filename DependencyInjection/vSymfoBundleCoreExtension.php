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

namespace vSymfo\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage DependencyInjection
 */
class vSymfoBundleCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->getAlias());
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('vsymfo_core.document', $config['document']);
        $container->setParameter('vsymfo_core.themes', $config['themes']);
        $container->setParameter('vsymfo_core.theme', $config['theme']);
        $container->setParameter('vsymfo_core.theme_backend', $config['theme_backend']);
        $container->setParameter('vsymfo_core.pagination.limit', $config['pagination']['limit']);
        $container->setParameter('vsymfo_core.pagination.pages_in_range', $config['pagination']['pages_in_range']);
        $container->setParameter('vsymfo_core.mailer.noreply_email', $config['mailer']['noreply_email']);
        $container->setParameter('vsymfo_core.mailer.default_email', $config['mailer']['default_email']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services/document.yml');
        $loader->load('services/entity.yml');
        $loader->load('services/images.yml');
        $loader->load('services/crud.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'vsymfo_core';
    }
}
