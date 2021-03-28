<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Contract;

use Http\Client\Common\Plugin;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient as BaseHttpClient;
use Http\Promise\Promise;
use Pandawa\Pavana\BatchRequest;
use Pandawa\Pavana\BatchResponse;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface as GuzzlePromise;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HttpClient extends HttpAsyncClient, BaseHttpClient
{
    const MAJOR_VERSION = 1;

    public function addPlugin(Plugin $plugin): void;

    public function prependPlugin(Plugin $plugin): void;

    /**
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return ResponseInterface|mixed
     */
    public function request(string $method, string $uri = '', array $options = []);

    public function requestAsync(string $method, string $uri = '', array $options = []): Promise;

    public function requestBatch(BatchRequest $requests): BatchResponse;

    public function requestBatchAsync(BatchRequest $requests): GuzzlePromise;
}
