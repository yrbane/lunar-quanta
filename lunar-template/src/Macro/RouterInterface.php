<?php

namespace Lunar\Template\Macro;

interface RouterInterface
{
    public function getRouteByName(string $name): ?array;
}
