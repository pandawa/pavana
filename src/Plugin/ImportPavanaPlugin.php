<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Plugin;

use Pandawa\Component\Foundation\Bundle\Plugin;
use Pandawa\Contracts\Config\LoaderInterface;
use Pandawa\Pavana\Contract\HttpClientRegistryInterface;
use Pandawa\Pavana\PavanaBundle;
use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ImportPavanaPlugin extends Plugin
{
    public function __construct(protected readonly string $path = 'Resources/pavana')
    {
    }

    public function configure(): void
    {
        $clients = $this->config()->get($this->getConfigKey(), []);

        if ($this->bundle->getApp()->configurationIsCached()) {
            $this->registry()->register($clients);

            return;
        }

        foreach ($this->getConfigs() as $configs) {
            $clients = [
                ...$clients,
                ...$configs
            ];
        }

        $this->config()->set(
            $this->getConfigKey(),
            $clients
        );

        $this->registry()->register($clients);
    }

    protected function getConfigs(): iterable
    {
        foreach (Finder::create()->in($this->bundle->getPath($this->path))->files() as $file) {
            yield $this->loader()->load($file->getRealPath());
        }
    }

    protected function config(): Config
    {
        return $this->bundle->getService('config');
    }

    protected function loader(): LoaderInterface
    {
        return $this->bundle->getApp()->get(LoaderInterface::class);
    }

    protected function getConfigKey(): string
    {
        return PavanaBundle::CLIENT_CONFIG_KEY . '.' . $this->bundle->getName();
    }

    protected function registry(): HttpClientRegistryInterface
    {
        return $this->bundle->getService(HttpClientRegistryInterface::class);
    }
}
