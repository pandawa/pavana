<?php

declare(strict_types=1);

namespace Unit;

use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\HttpAsyncClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Promise\Promise;
use Illuminate\Container\Container;
use Pandawa\Pavana\HttpClient\BatchRequest;
use Pandawa\Pavana\HttpClient\BatchResponse;
use Pandawa\Pavana\Contract\HttpClientInterface;
use Pandawa\Pavana\Factory\HttpClientFactory;
use Pandawa\Pavana\Factory\HttpHandlerFactory;
use Pandawa\Pavana\Factory\RequestFactory;
use Pandawa\Pavana\HttpClient\Options;
use Pandawa\Pavana\HttpClient\Plugin\JsonDecodePlugin;
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
     * @return HttpClientInterface
     */
    public function testHttpClientFactory(HttpHandlerFactory $httpHandlerFactory, RequestFactory $requestFactory): HttpClientInterface
    {
        $factory = new HttpClientFactory(new Container(), $httpHandlerFactory, Psr17FactoryDiscovery::findStreamFactory(), [
            'timeout'         => 10,
            'request_factory' => $requestFactory,
            'http_errors'     => true,
        ]);
        $httpClient = $factory->create();

        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);

        return $httpClient;
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClientInterface $httpClient
     */
    public function testSyncRequest(HttpClientInterface $httpClient): void
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
     * @param HttpClientInterface $httpClient
     */
    public function testAsyncRequest(HttpClientInterface $httpClient): void
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
     * @param HttpClientInterface $httpClient
     */
    public function testBatchRequests(HttpClientInterface $httpClient): void
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
        $this->assertSame(404, $failure['not_found']->getCode());
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClientInterface $httpClient
     */
    public function testPlugin(HttpClientInterface $httpClient): void
    {
        $httpClient = clone $httpClient;
        $httpClient->addPlugin(new JsonDecodePlugin());

        $data = $httpClient->request('GET', 'ping');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('pong', $data['data']);
    }

    /**
     * @depends testHttpClientFactory
     *
     * @param HttpClientInterface $httpClient
     */
    public function testHttpException(HttpClientInterface $httpClient): void
    {
        try {
            $httpClient->request('GET', 'ping2');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ClientErrorException::class, $e);

            if ($e instanceof ClientErrorException) {
                $this->assertSame(404, $e->getCode());
                $this->assertInstanceOf(ResponseInterface::class, $e->getResponse());
            }
        }
    }

    private function getOptions(): Options
    {
        return new Options([
            'base_uri' => 'https://api.ammana.id/v3/',
            'headers'  => [
                'Accept' => 'application/json',
            ],
        ]);
    }
}
