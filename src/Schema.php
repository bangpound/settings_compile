<?php

namespace Drupal\Settings;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Schema implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('drupal');
        $rootNode->children()
            ->arrayNode('settings')
                ->isRequired()
                ->useAttributeAsKey('name')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('ini')
                ->prototype('variable')
                    ->treatNullLike(array())
                ->end()
            ->end()
            ->arrayNode('include')
                ->addDefaultsIfNotSet()
                ->treatNullLike(array())
                ->children()
                    ->arrayNode('require')->prototype('variable')->end()->end()
                    ->arrayNode('require_once')->prototype('variable')->end()->end()
                    ->arrayNode('include')->prototype('variable')->end()->end()
                    ->arrayNode('include_once')->prototype('variable')->end()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
