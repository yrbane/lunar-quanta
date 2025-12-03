<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

use App\Service\Core\Debug\Dumper;

if (!function_exists('dump')) {
    /**
     * Alias global : redirige vers {@see Dumper::dump()}.
     */
    function dump(mixed ...$vars): void
    {
        Dumper::dump(...$vars);
    }

    function dump_flush(): void
    {
        Dumper::flush();
    }
}
