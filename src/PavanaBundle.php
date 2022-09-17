<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Pandawa\Bundle\DependencyInjectionBundle\Plugin\ImportServicesPlugin;
use Pandawa\Bundle\FoundationBundle\Plugin\ImportConfigurationPlugin;
use Pandawa\Component\Foundation\Bundle\Bundle;
use Pandawa\Contracts\Foundation\HasPluginInterface;
use Pandawa\Pavana\Contract\HttpClientFactoryInterface;
use Pandawa\Pavana\Contract\HttpClientRegistryInterface;
use Pandawa\Pavana\Contract\HttpHandlerFactoryInterface;
use Pandawa\Pavana\Factory\HttpClientFactory;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class PavanaBundle extends Bundle implements HasPluginInterface
{
    const CLIENT_CONFIG_KEY = 'pandawa.clients';

    public function configure(): void
    {
        $this->registerHttpClientFactory();

        $this->httpClientRegistry()->register(
            $this->getConfig('clients', [])
        );
    }

    public function plugins(): array
    {
        return [
            new ImportConfigurationPlugin(),
            new ImportServicesPlugin(),
        ];
    }

    protected function registerHttpClientFactory(): void
    {
        $this->app->singleton(HttpClientFactoryInterface::class, function (Application $app) {
            return new HttpClientFactory(
                $app,
                $app->get(HttpHandlerFactoryInterface::class),
                $app->get(StreamFactoryInterface::class),
                Arr::except($app['config']['pavana'], ['clients'])
            );
        });
    }

    protected function httpClientRegistry(): HttpClientRegistryInterface
    {
        return $this->getService(HttpClientRegistryInterface::class);
    }
}
