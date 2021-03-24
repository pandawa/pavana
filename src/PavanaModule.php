<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use Http\Discovery\Psr17FactoryDiscovery;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Pandawa\Component\Module\AbstractModule;
use Pandawa\Pavana\Contract\HttpClientFactory as HttpClientFactoryContract;
use Pandawa\Pavana\Contract\HttpHandlerFactory as HttpHandlerFactoryContract;
use Pandawa\Pavana\Factory\HttpClientFactory;
use Pandawa\Pavana\Factory\HttpHandlerFactory;
use Pandawa\Pavana\Plugin\JsonDecodePlugin;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class PavanaModule extends AbstractModule
{
    use PavanaProvider;

    public function provides(): array
    {
        return array_merge(
            [
                HttpHandlerFactoryContract::class,
                HttpClientFactoryContract::class,
            ],
            $this->registeredClients
        );
    }

    protected function build(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pavana.php' => config_path('pavana.php'),
            ], 'config');
        }
    }

    protected function init(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pavana.php', 'pavana');

        $this->registerPlugins();
        $this->registerHttpHandlerFactory();
        $this->registerHttpClientFactory();
        $this->registerScopeClients($this->app['config']['pavana']['clients'] ?? []);
    }

    private function registerPlugins(): void
    {
        $this->app->bind('pavana.plugins.json_decode', JsonDecodePlugin::class);
    }

    private function registerHttpHandlerFactory(): void
    {
        $this->app->singleton(HttpHandlerFactoryContract::class, HttpHandlerFactory::class);
    }

    private function registerHttpClientFactory(): void
    {
        $this->app->singleton(StreamFactoryInterface::class, function () {
            return Psr17FactoryDiscovery::findStreamFactory();
        });

        $this->app->singleton(HttpClientFactoryContract::class, function (Application $app) {
            return new HttpClientFactory(
                $app,
                $app->get(HttpHandlerFactoryContract::class),
                $app->get(StreamFactoryInterface::class),
                Arr::except($app['config']['pavana'], ['clients'])
            );
        });
    }
}
