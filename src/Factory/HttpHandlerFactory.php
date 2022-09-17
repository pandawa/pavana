<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Factory;

use GuzzleHttp\RequestOptions as GuzzleHttpClientOptions;
use Http\Adapter\Guzzle7\Client as GuzzleHttpClient;
use Http\Client\HttpAsyncClient;
use Pandawa\Pavana\Contract\HttpHandlerFactoryInterface;
use Pandawa\Pavana\HttpClient\Options;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\HttplugClient as SymfonyHttplugClient;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class HttpHandlerFactory implements HttpHandlerFactoryInterface
{
    public function create(Options $options): HttpAsyncClient
    {
        return match(true) {
            class_exists(SymfonyHttplugClient::class) => $this->createSymfonyHttpClient($options),
            class_exists(GuzzleHttpClient::class) => $this->createGuzzleHttpClient($options),
            default => throw new RuntimeException('There is no http handler detected!')
        };
    }

    private function createSymfonyHttpClient(Options $options): SymfonyHttplugClient
    {
        return new SymfonyHttplugClient(
            SymfonyHttpClient::create($this->filterOptions(
                [
                    'max_duration' => $options->getTimeout(),
                    'http_proxy'   => $options->getHttpProxy(),
                ]
            ))
        );
    }

    private function createGuzzleHttpClient(Options $options): GuzzleHttpClient
    {
        return GuzzleHttpClient::createWithConfig($this->filterOptions(
            [
                GuzzleHttpClientOptions::TIMEOUT         => $options->getTimeout(),
                GuzzleHttpClientOptions::CONNECT_TIMEOUT => $options->getTimeout(),
                GuzzleHttpClientOptions::PROXY           => $options->getHttpProxy(),
                GuzzleHttpClientOptions::HTTP_ERRORS     => false,
            ]
        ));
    }

    private function filterOptions(array $options): array
    {
        return array_filter($options, function ($value) {
            return null !== $value;
        });
    }
}
