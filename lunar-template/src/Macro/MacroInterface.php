<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace Lunar\Template\Macro;

interface MacroInterface
{
    public function getName(): string;

    /**
     * La méthode appelée quand le moteur appelle la macro.
     *
     * @param array<int, mixed> $args
     * 
     * @return mixed
     */
    public function execute(array $args);
}
