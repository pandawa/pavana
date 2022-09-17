<?php

declare(strict_types=1);

namespace Pandawa\Pavana\HttpClient;

use Illuminate\Contracts\Container\Container;
use Pandawa\Pavana\Contract\HttpClientFactoryInterface;
use Pandawa\Pavana\Contract\HttpClientRegistryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HttpClientRegistry implements HttpClientRegistryInterface
{
    public function __construct(
        protected readonly Container $container,
        protected readonly HttpClientFactoryInterface $httpClientFactory,
    ) {
    }

    public function register(array $clients): void
    {
        foreach ($clients as $name => $client) {
            $this->container->singleton($name, fn() => $this->httpClientFactory->create(
                $client
            ));
        }
    }
}
