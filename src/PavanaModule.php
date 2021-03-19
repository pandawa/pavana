<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Pandawa\Component\Module\AbstractModule;
use Pandawa\Pavana\Contract\HttpClientFactory as HttpClientFactoryContract;
use Pandawa\Pavana\Contract\HttpHandlerFactory as HttpHandlerFactoryContract;
use Pandawa\Pavana\Factory\HttpClientFactory;
use Pandawa\Pavana\Factory\HttpHandlerFactory;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class PavanaModule extends AbstractModule
{
    public function provides(): array
    {
        return array_merge(
            [
                HttpHandlerFactoryContract::class,
                HttpClientFactoryContract::class,
            ],
            array_keys($this->app['config']['pavana'])
        );
    }

    protected function build(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/pavana.php' => config_path('pavana.php'),
            ], 'config');
        }
    }

    protected function init(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/pavana.php', 'pavana');

        $this->registerHttpHandlerFactory();
        $this->registerHttpClientFactory();

        foreach ($this->app['config']['pavana'] ?? [] as $key => $client) {
            $this->app->singleton($key, function (Application $app) use ($client) {
                return $app[HttpClientFactoryContract::class]->create($client);
            });
        }
    }

    private function registerHttpHandlerFactory(): void
    {
        $this->app->singleton(HttpHandlerFactoryContract::class, HttpHandlerFactory::class);
    }

    private function registerHttpClientFactory(): void
    {
        $this->app->singleton(HttpClientFactoryContract::class, function (Application $app) {
            return new HttpClientFactory(
                $app,
                $app->get(HttpHandlerFactoryContract::class),
                Arr::except($app['config']['pavana'], ['clients'])
            );
        });
    }
}
