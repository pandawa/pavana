<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Factory;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\HttpAsyncClient;
use Illuminate\Contracts\Container\Container;
use Pandawa\Pavana\Contract\HttpClientFactoryInterface;
use Pandawa\Pavana\Contract\HttpClientInterface;
use Pandawa\Pavana\Contract\RequestFactoryInterface;
use Pandawa\Pavana\HttpClient\HttpClient;
use Pandawa\Pavana\HttpClient\Options;
use Pandawa\Pavana\HttpClient\Plugin\GzipEncoderPlugin;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class HttpClientFactory implements HttpClientFactoryInterface
{
    public function __construct(
        protected readonly Container $container,
        protected readonly HttpHandlerFactory $httpHandlerFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly array $defaults = []
    ) {
    }

    public function create(array $options = []): HttpClientInterface
    {
        $options = $this->createOptions($options);

        return new HttpClient(
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

    private function createRequestFactory(Options $options): RequestFactoryInterface
    {
        if (null !== $requestFactory = $options->getRequestFactory()) {
            if ($requestFactory instanceof RequestFactoryInterface) {
                return $requestFactory;
            }

            return $this->container->get($requestFactory);
        }

        return new RequestFactory($options);
    }

    private function createPlugins(Options $options): array
    {
        $defaults = [
            new RetryPlugin(['retries' => $options->getRetries()]),
        ];

        if ($options->isHttpErrors()) {
            $defaults[] = new ErrorPlugin();
        }

        if ($options->isEnableCompression()) {
            $defaults[] = new GzipEncoderPlugin($this->streamFactory);
            $defaults[] = new DecoderPlugin();
        }

        return [
            ...array_map(function ($plugin) {
                if ($plugin instanceof Plugin) {
                    return $plugin;
                }

                return $this->container->get($plugin);
            }, $options->getPlugins()),
            ...$defaults,
        ];
    }
}
