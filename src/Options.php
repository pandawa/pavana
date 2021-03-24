<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use GuzzleHttp\Psr7;
use Http\Client\HttpAsyncClient;
use Pandawa\Pavana\Contract\HttpClient;
use Pandawa\Pavana\Contract\RequestFactory;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class Options
{
    const HEADERS = 'headers';
    const JSON = 'json';
    const QUERY = 'query';
    const FORM_PARAMS = 'form_params';
    const MULTIPART = 'multipart';
    const BODY = 'body';

    private OptionsResolver $resolver;
    private array $options;

    public function __construct(array $options = [])
    {
        $this->resolver = new OptionsResolver();

        $this->configureDefaults($options);

        $this->options = $this->resolver->resolve($options);

        if ($baseUri = $this->options['base_uri'] ?? null) {
            $this->options['base_uri'] = Psr7\Utils::uriFor($baseUri);
        }
    }

    public function getTimeout(): int
    {
        return (int)$this->options['timeout'];
    }

    public function getHttpProxy(): ?string
    {
        return $this->options['http_proxy'];
    }

    public function getBaseUri(): ?UriInterface
    {
        return $this->options['base_uri'];
    }

    public function isHttpErrors(): bool
    {
        return $this->options['http_errors'];
    }

    public function isEnableCompression(): bool
    {
        return $this->options['enable_compression'];
    }

    public function getHeaders(): array
    {
        return $this->options['headers'];
    }

    public function getVersion(): string
    {
        return $this->options['version'];
    }

    public function getPlugins(): array
    {
        return $this->options['plugins'];
    }

    public function getUserAgent(): string
    {
        return $this->options['user_agent'];
    }

    public function getRetries(): int
    {
        return $this->options['retries'];
    }

    /**
     * @return HttpAsyncClient|string|null
     */
    public function getHttpHandler()
    {
        return $this->options['http_handler'];
    }

    /**
     * @return RequestFactory|string|null
     */
    public function getRequestFactory()
    {
        return $this->options['request_factory'];
    }

    public function toArray(): array
    {
        return $this->options;
    }

    private function configureDefaults(array $options): void
    {
        $this->resolver->setDefaults([
            'timeout'            => 5,
            'retries'            => 1,
            'http_proxy'         => null,
            'http_errors'        => false,
            'enable_compression' => true,
            'base_uri'           => null,
            'user_agent'         => sprintf('Pavana/%d', HttpClient::MAJOR_VERSION),
            'headers'            => [],
            'version'            => '1.1',
            'http_handler'       => null,
            'request_factory'    => null,
            'plugins'            => [],
        ]);
    }
}
