<?php

declare(strict_types=1);

namespace Nivas\Bundle\SwitchBaseCurrencyBundle;

use Nivas\Bundle\SwitchBaseCurrencyBundle\DependencyInjection\SwitchBaseCurrencyExtension;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SwitchBaseCurrencyBundle extends Bundle
{
    use SyliusPluginTrait;

    // bundle debugging
    // public function boot(): void
    // {
    //     parent::boot();
    //     echo "SwitchBaseCurrencyBundle booted!" . PHP_EOL;
    // }

    /**
     * This method returns an instance of the bundle's extension class (SwitchBaseCurrencyExtension),
     * which is responsible for loading and managing the bundle's configuration.
     * By overriding this method, we ensure that the correct extension class is used for the bundle.
     *
     * @return SwitchBaseCurrencyExtension The bundle's extension instance
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SwitchBaseCurrencyExtension();
        }

        return $this->extension;
    }
}
