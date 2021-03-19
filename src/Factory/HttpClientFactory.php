<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Factory;

use Http\Client\Common\Plugin;
use Http\Client\HttpAsyncClient;
use Illuminate\Contracts\Container\Container;
use Pandawa\Pavana\Contract\HttpClient as HttpClientContract;
use Pandawa\Pavana\Contract\HttpClientFactory as HttpClientFactoryContract;
use Pandawa\Pavana\Contract\RequestFactory as RequestFactoryContract;
use Pandawa\Pavana\HttpClient;
use Pandawa\Pavana\Options;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class HttpClientFactory implements HttpClientFactoryContract
{
    private Container $container;
    private HttpHandlerFactory $httpHandlerFactory;
    private array $defaults;

    public function __construct(Container $container, HttpHandlerFactory $httpHandlerFactory, array $defaults = [])
    {
        $this->container = $container;
        $this->httpHandlerFactory = $httpHandlerFactory;
        $this->defaults = $defaults;
    }

    public function create(array $options = []): HttpClientContract
    {
        $options = $this->createOptions($options);

        return new HttpClient(
            $options,
            $this->createHttpHandler($options),
            $this->createRequestFactory($options),
            $this->createPlugins($options)
        );
    }

    private function createOptions(array $options): Options
    {
        return new Options($options + $this->defaults);
    }

    private function createHttpHandler(Options $options): HttpAsyncClient
    {
        if (null !== $handler = $options->getHttpHandler()) {
            if ($handler instanceof HttpAsyncClient) {
                return $handler;
            }

            return $this->container->get($handler);
        }

        return $this->httpHandlerFactory->create($options);
    }

    private function createRequestFactory(Options $options): RequestFactoryContract
    {
        if (null !== $requestFactory = $options->getRequestFactory()) {
            if ($requestFactory instanceof RequestFactoryContract) {
                return $requestFactory;
            }

            return $this->container->get($requestFactory);
        }

        return new RequestFactory($options);
    }

    private function createPlugins(Options $options): array
    {
        return array_map(function ($plugin) {
            if ($plugin instanceof Plugin) {
                return $plugin;
            }

            return $this->container->get($plugin);
        }, $options->getPlugins());
    }
}
