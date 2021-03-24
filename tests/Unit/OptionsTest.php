<?php

declare(strict_types=1);

namespace Unit;

use Pandawa\Pavana\Options;
use Pandawa\Pavana\Test\TestCase;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class OptionsTest extends TestCase
{
    public function testGlobalDefaultOptions(): void
    {
        $options = new Options(['http_errors' => true] + $this->getDefaults());

        $this->assertSame(10, $options->getTimeout());
        $this->assertSame(['json'], $options->getPlugins());
        $this->assertSame(true, $options->isHttpErrors());
    }

    public function testOverwriteOptions(): void
    {
        $options = new Options(['timeout' => 30, 'plugins' => ['xml']] + $this->getDefaults());

        $this->assertSame(30, $options->getTimeout());
        $this->assertSame(['xml'], $options->getPlugins());
    }

    private function getDefaults(): array
    {
        return [
            'timeout' => 10,
            'plugins' => ['json'],
        ];
    }
}
