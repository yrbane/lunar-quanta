<?php
declare(strict_types=1);

namespace Tests\TempControllers\Attribute;

#[
Attribute(
Attribute::TARGET_METHOD |
Attribute::TARGET_CLASS
)
]
class Route
{
    public string $path;
    public array $methods;
    public ?string $name;

    public function __construct(string $path, array $methods = ['GET'], ?string $name = null)
    {
        $this->path = $path;
        $this->methods = $methods;
        $this->name = $name;
    }
}
