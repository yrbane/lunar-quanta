<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Command
{
    public function __construct(
        public string $name,
        public string $description = ''
    ) {}
}
