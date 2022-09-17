<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HttpClientRegistryInterface
{
    public function register(array $clients): void;
}
