<?php

namespace Knp\Bundle\PaginatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('peerj_mpdf')
            ->addDefaultsIfNotSet()
            ->end()
        ;
        return $builder;
    }
}
