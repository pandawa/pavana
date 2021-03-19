<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class PluginChain
{
    /**
     * @var Plugin[]
     */
    private array $plugins;

    /**
     * @var callable
     */
    private $callback;

    public function __construct(array $plugins, callable $callback)
    {
        $this->plugins = $plugins;
        $this->callback = $callback;
    }

    public function __invoke(RequestInterface $request): Promise
    {
        return $this->create()($request);
    }

    private function create(): callable
    {
        $lastCallback = $this->callback;

        foreach (array_reverse($this->plugins) as $plugin) {
            $lastCallback = function (RequestInterface $request) use ($plugin, $lastCallback) {
                return $plugin->handleRequest($request, $lastCallback, $this);
            };
        }

        return $lastCallback;
    }
}
