<?php

namespace Tests\Feature;

use Dotenv\Dotenv;
use Dotenv\Loader\Loader;
use Dotenv\Parser\Parser;
use Dotenv\Store\StringStore;
use Illuminate\Support\Env;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class EnvironmentIsolationTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_dotenv_values_are_available_without_changing_process_environment(): void
    {
        $variable = 'SIMANO_DOTENV_ISOLATION_TEST';
        unset($_ENV[$variable], $_SERVER[$variable]);
        putenv($variable);

        require __DIR__.'/../../bootstrap/app.php';

        (new Dotenv(
            new StringStore($variable.'=synthetic-value'),
            new Parser,
            new Loader,
            Env::getRepository(),
        ))->load();

        $this->assertSame('synthetic-value', Env::get($variable));
        $this->assertFalse(getenv($variable));
    }
}
