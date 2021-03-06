<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_navigation');

        $node = $rootNode->children();
        $node->scalarNode('js_routing_filename_prefix')
            ->defaultValue('')
            ->info('The prefix in the name of the file with a list of js routes.')
            ->beforeNormalization()
                ->always(function ($data) {
                    return ltrim($data, '/');
                })
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'max_items'       => ['value' => 20],
                'title_suffix'    => ['value' => ''],
                'title_delimiter' => ['value' => '-'],
                'breadcrumb_menu' => ['value' => 'application_menu']
            ]
        );

        return $treeBuilder;
    }
}
