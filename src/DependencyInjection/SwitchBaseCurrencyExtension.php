<?php

namespace Nivas\Bundle\SwitchBaseCurrencyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SwitchBaseCurrencyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // echo "SwitchBaseCurrencyExtension loaded!" . PHP_EOL; // Add this line for debugging

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
