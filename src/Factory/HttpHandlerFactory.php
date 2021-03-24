<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Factory;

use GuzzleHttp\RequestOptions as GuzzleHttpClientOptions;
use Http\Adapter\Guzzle7\Client as GuzzleHttpClient;
use Http\Client\HttpAsyncClient;
use Pandawa\Pavana\Contract\HttpHandlerFactory as HttpHandlerFactoryContract;
use Pandawa\Pavana\Options;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\HttplugClient as SymfonyHttplugClient;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class HttpHandlerFactory implements HttpHandlerFactoryContract
{
    public function create(Options $options): HttpAsyncClient
    {
        if (class_exists(SymfonyHttplugClient::class)) {
            $httpClient = $this->createSymfonyHttpClient($options);
        } elseif (class_exists(GuzzleHttpClient::class)) {
            $httpClient = $this->createGuzzleHttpClient($options);
        } else {
            throw new \RuntimeException('There is no http handler detected!');
        }

        return $httpClient;
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
