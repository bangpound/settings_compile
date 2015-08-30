<?php

namespace Drupal\Settings;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Schema implements ConfigurationInterface
{
    private $name;

    public function __construct($name = 'default')
    {
        $this->name = $name;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $siteNode = $treeBuilder->root($this->name);
        $siteNode->children()
            ->arrayNode('aliases')
                ->prototype('scalar')
                ->end()
            ->end()
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
