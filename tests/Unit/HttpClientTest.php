<?php

declare(strict_types=1);

namespace Unit;

use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Illuminate\Container\Container;
use Pandawa\Pavana\BatchRequest;
use Pandawa\Pavana\BatchResponse;
use Pandawa\Pavana\Contract\HttpClient;
use Pandawa\Pavana\Factory\HttpClientFactory;
use Pandawa\Pavana\Factory\HttpHandlerFactory;
use Pandawa\Pavana\Factory\RequestFactory;
use Pandawa\Pavana\Options;
use Pandawa\Pavana\Plugin\JsonDecodePlugin;
use Pandawa\Pavana\Test\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HttpClientTest extends TestCase
{
    public function testHttpHandlerFactory(): HttpHandlerFactory
    {
        $factory = new HttpHandlerFactory();

        $this->assertInstanceOf(HttpAsyncClient::class, $factory->create($this->getOptions()));

        return $factory;
    }

    public function testRequestFactory(): RequestFactory
    {
        $options = $this->getOptions();
        $factory = new RequestFactory($options);

        $request = $factory->create('GET', 'ping', [
            Options::QUERY => ['id' => 1],
        ]);

        $this->assertInstanceOf(RequestInterface::class, $request);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(sprintf('%sping?id=1', $options->getBaseUri()), (string)$request->getUri());

        $user = [
            'name'  => 'Iqbal Maulana',
            'email' => 'iq.bluejack@gmail.com',
        ];

        $request = $factory->create('POST', 'users', [
            Options::JSON => $user,
        ]);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(sprintf('%susers', $options->getBaseUri()), (string)$request->getUri());
        $this->assertSame(json_encode($user), $request->getBody()->getContents());
        $this->assertSame(
            [
                'Accept'       => ['application/json'],
                'User-Agent'   => [$options->getUserAgent()],
                'Content-Type' => ['application/json'],
                'Host'         => [$options->getBaseUri()->getHost()],
            ],
            $request->getHeaders()
        );

        return $factory;
    }

    /**
     * @depends testHttpHandlerFactory
     * @depends testRequestFactory
     *
     * @param HttpHandlerFactory $httpHandlerFactory
     * @param RequestFactory     $requestFactory
     *
     * @return HttpClient
     */
    public function testHttpClientFactory(HttpHandlerFactory $httpHandlerFactory, RequestFactory $requestFactory): HttpClient
    {
        $factory = new HttpClientFactory(new Container(), $httpHandlerFactory, [
            'timeout'         => 10,
            'request_factory' => $requestFactory,
        ]);
        $httpClient = $factory->create();

        $this->assertInstanceOf(HttpClient::class, $httpClient);

        return $httpClient;
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClient $httpClient
     */
    public function testSyncRequest(HttpClient $httpClient): void
    {
        $response = $httpClient->request('GET', 'ping');
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($data);
        $this->assertSame('pong', $data['data']);
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClient $httpClient
     */
    public function testAsyncRequest(HttpClient $httpClient): void
    {
        $promise = $httpClient->requestAsync('GET', 'ping');

        $this->assertInstanceOf(Promise::class, $promise);

        $promise->then(function (ResponseInterface $response) {
            $data = json_decode($response->getBody()->getContents(), true);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertIsArray($data);
            $this->assertSame('pong', $data['data']);
        });

        $promise->wait();
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClient $httpClient
     */
    public function testBatchRequests(HttpClient $httpClient): void
    {
        $responses = $httpClient->requestBatch(new BatchRequest([
            'ping'      => [
                'method' => 'GET',
                'uri'    => 'ping',
            ],
            'not_found' => [
                'method' => 'GET',
                'uri'    => 'test-not-found',
            ],
        ]));

        $this->assertInstanceOf(BatchResponse::class, $responses);
        $this->assertTrue($responses->hasFailure());
        $this->assertSame(1, $responses->getTotalFailure());

        $success = $responses->getSuccessResponses();

        $this->assertArrayHasKey('ping', $success);
        $this->assertSame(200, $success['ping']->getStatusCode());

        $failure = $responses->getFailureResponses();

        $this->assertArrayHasKey('not_found', $failure);
        $this->assertSame(404, $failure['not_found']->getStatusCode());
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClient $httpClient
     */
    public function testPlugin(HttpClient $httpClient): void
    {
        $httpClient = clone $httpClient;
        $httpClient->addPlugin(new JsonDecodePlugin());

        $data = $httpClient->request('GET', 'ping');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('pong', $data['data']);
    }

    private function getOptions(): Options
    {
        return new Options([
            'base_uri' => 'https://api.ammana.id/v3/',
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }
}
