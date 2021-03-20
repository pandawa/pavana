<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Pandawa\Component\Module\AbstractModule;
use Pandawa\Pavana\Contract\HttpClientFactory as HttpClientFactoryContract;

/**
 * @mixin AbstractModule
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait PavanaProvider
{
    protected array $registeredClients = [];

    public function registerPavanaProvider(): void
    {
        $configName = Str::snake($this->getModuleName());

        if (!empty($clients = $this->app['config']->get(sprintf('modules.pavana.%s', $configName), $configName))) {
            $this->registerScopeClients($clients);
        }
    }

    protected function registerScopeClients(array $clients): void
    {
        $this->registeredClients = array_keys($clients);

        foreach ($clients as $key => $client) {
            $this->app->singleton($key, function (Application $app) use ($client) {
                return $app[HttpClientFactoryContract::class]->create($client);
            });
        }
    }
}
