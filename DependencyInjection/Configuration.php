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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->alias);
        $rootNode
            ->children()
                ->arrayNode('themes')
                    ->defaultValue(array())
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('theme')->end()
                ->scalarNode('theme_backend')->end()
                ->arrayNode('document')
                    ->children()
                        ->scalarNode('sitename')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('title_default')->end()
                        ->enumNode('title_mode')
                            ->values(array('only_name', 'only_title', 'first_name', 'first_title'))
                            ->defaultValue('first_name')
                        ->end()
                        ->scalarNode('title_separator')
                            ->defaultValue('-')
                        ->end()
                        ->scalarNode('description')->end()
                        ->scalarNode('keywords')->end()
                        ->scalarNode('meta_viewport')
                            ->defaultValue('width=device-width, initial-scale=1.0')
                        ->end()
                        ->booleanNode('favicon_enable')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('favicon_tile_color')
                            ->defaultValue('#ffffff')
                        ->end()
                        ->integerNode('resources_cache_lifetime')
                            ->defaultValue(0)
                            ->min(0)
                        ->end()
                        ->integerNode('resources_loading_timeout')
                            ->min(0)
                            ->defaultValue(60000)
                        ->end()
                        ->integerNode('css_callback_timeout')
                            ->min(0)
                            ->defaultValue(0)
                        ->end()
                        ->booleanNode('versioning_enable')
                            ->defaultFalse()
                        ->end()
                        ->integerNode('versioning_version')
                            ->defaultValue(1)
                            ->min(0)
                        ->end()
                        ->booleanNode('versioning_timestamp')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('cdn_enable')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('cdn_javascript')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('cdn_css')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('cdn_image')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('pdf_wkhtmltopdf_bin')
                            ->defaultValue('/usr/local/bin/wkhtmltopdf')
                        ->end()
                        ->integerNode('pdf_duration')
                            ->min(-1)
                            ->defaultValue(3600)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('pagination')
                    ->children()
                        ->integerNode('limit')
                            ->min(1)
                            ->defaultValue(20)
                        ->end()
                        ->integerNode('pages_in_range')
                            ->min(1)
                            ->defaultValue(5)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mailer')
                    ->children()
                        ->scalarNode('noreply_email')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('default_email')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('login_admin_shield_route_login')
                    ->children()
                        ->scalarNode('name')->end()
                        ->arrayNode('params')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
