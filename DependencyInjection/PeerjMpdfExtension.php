<?php

namespace Peerj\Bundle\MpdfBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Processor;

class PeerjMpdfExtension extends Extension
{
    /**
     * Build the extension services
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.xml');

        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $additionalFonts = array();
        $copyFontFiles = array();
        if (!empty($config['fonts'])) {
            foreach($config['fonts'] as $name => $font) {
                $additionalFonts[$name] = $font['family'];

                // only copy if path is present
                if (isset($font['path'])) {
                    foreach($font['family'] as $type => $file) {
                        $copyFontFiles[] = array('path' => $font['path'], 'file' => $file);
                    }
                }
            }
        }

        $container->getDefinition('peerj_mpdf')->addMethodCall('initMpdf', array($additionalFonts));
        $container->getDefinition('peerj_mpdf')->addMethodCall('copyFontFiles', array($copyFontFiles));
    }
}
