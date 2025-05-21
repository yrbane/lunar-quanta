<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace App\Service\Core\Template\Macro;

interface MacroInterface
{
    public function getName(): string;

    /**
     * La méthode appelée quand le moteur appelle la macro.
     *
     * @return mixed
     */
    /**
     * @param array<int, mixed> $args
     */
    public function execute(array $args);
}
