<?php

namespace Peerj\Bundle\MpdfBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('peerj_mpdf');
        if (\method_exists($builder, 'getRootNode')) {
            $rootNode = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $builder->root('peerj_mpdf', 'array');
        }

        $rootNode->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('format')->end()
                ->scalarNode('tmp_dir')->end()
                ->arrayNode('fonts')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('path')->end()
                            ->arrayNode('data')
                                ->children()
                                    ->scalarNode('R')->isRequired()->end()
                                    ->scalarNode('B')->end()
                                    ->scalarNode('I')->end()
                                    ->scalarNode('BI')->end()
                                    ->scalarNode('indic')->end()
                                    ->scalarNode('sip-ext')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
