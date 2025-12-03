<?php
declare(strict_types=1);

namespace Tests\TempControllers;

use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;
use Tests\TempControllers\Attribute\Route;

class TestController2
{
    public function __construct()
    {
    }

    #[Route('/another-action', methods: ['GET'], name: 'test2_another_action')]
    public function anotherAction(Request $request): Response
    {
        return new Response('TestController2::anotherAction');
    }
}
