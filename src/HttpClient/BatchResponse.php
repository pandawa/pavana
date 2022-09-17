<?php

declare(strict_types=1);

namespace Pandawa\Pavana\HttpClient;

use Http\Client\Exception\HttpException;
use Http\Promise\Promise;
use Psr\Http\Message\ResponseInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class BatchResponse
{
    private array $responses = [
        'success' => [],
        'failure' => [],
    ];

    private int $totalFailure = 0;

    public function __construct()
    {
    }

    public static function createFromArray(array $responses): self
    {
        $batchResponse = new self();

        array_walk($responses, function (array $response, $key) use ($batchResponse) {
            if (Promise::FULFILLED === $response['state']) {
                if ($response['value'] instanceof ResponseInterface
                    && $response['value']->getStatusCode() >= 400) {
                    $batchResponse->addFailure($key, $response['value']);
                } else {
                    $batchResponse->addSuccess($key, $response['value']);
                }
            } else {
                $batchResponse->addFailure($key, $response['reason']);
            }
        });

        return $batchResponse;
    }

    public function addSuccess(string $key, $response): void
    {
        $this->responses['success'][$key] = $response;
    }

    public function addFailure(string $key, $response): void
    {
        $this->responses['failure'][$key] = $response;
        $this->totalFailure++;
    }

    /**
     * @return ResponseInterface[]|mixed
     */
    public function getSuccessResponses(): array
    {
        return $this->responses['success'];
    }

    /**
     * @return HttpException[]|mixed
     */
    public function getFailureResponses(): array
    {
        return $this->responses['failure'];
    }

    public function toArray(): array
    {
        return $this->responses;
    }

    public function getTotalFailure(): int
    {
        return $this->totalFailure;
    }

    public function hasFailure(): bool
    {
        return $this->totalFailure > 0;
    }
}
