<?php

declare(strict_types=1);

namespace Pandawa\Pavana\HttpClient;

use GuzzleHttp\Promise\PromiseInterface as GuzzlePromise;
use GuzzleHttp\Promise\Utils;
use Http\Client\Common\Plugin;
use Http\Client\HttpAsyncClient as HttpAsyncClientContract;
use Http\Promise\Promise;
use Illuminate\Support\Arr;
use Pandawa\Pavana\Contract\HttpClientInterface;
use Pandawa\Pavana\Contract\RequestFactoryInterface;
use Pandawa\Pavana\HttpClient\Plugin\PluginChain;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class HttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpAsyncClientContract $httpHandler,
        private readonly RequestFactoryInterface $requestFactory,
        private array $plugins = []
    ) {
    }

    public function addPlugin(Plugin $plugin): void
    {
        $this->plugins = Arr::prepend($this->plugins, $plugin);
    }

    public function prependPlugin(Plugin $plugin): void
    {
        $this->plugins[] = $plugin;
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->sendAsyncRequest($request)->wait();
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $pluginChain = $this->createPluginChain($this->plugins, function (RequestInterface $request) {
            return $this->httpHandler->sendAsyncRequest($request);
        });

        return $pluginChain($request);
    }

    public function request(string $method, string $uri = '', array $options = [])
    {
        return $this->requestAsync($method, $uri, $options)->wait();
    }

    public function requestAsync(string $method, string $uri = '', array $options = []): Promise
    {
        return $this->sendAsyncRequest($this->requestFactory->create($method, $uri, $options));
    }

    public function requestBatch(BatchRequest $requests): BatchResponse
    {
        return BatchResponse::createFromArray($this->requestBatchAsync($requests)->wait());
    }

    public function requestBatchAsync(BatchRequest $requests): GuzzlePromise
    {
        $promises = $requests->reduce(function (array $promises, array $request) {
            return array_merge($promises, [
                $request['response_key'] => $this->requestAsync(
                    $request['method'],
                    $request['uri'],
                    $request['options']
                ),
            ]);
        });

        return Utils::settle($promises);
    }

    private function createPluginChain(array $plugins, callable $callback): callable
    {
        return new PluginChain($plugins, $callback);
    }
}
