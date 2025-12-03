<?php
declare(strict_types=1);

namespace Tests\TempControllers;

use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;
use Tests\TempControllers\Attribute\Route;

#[Route('/prefix')]
class TestController1
{
    public function __construct()
    {
    }

    #[Route('/action1', methods: ['GET'], name: 'test1_action1')]
    public function action1(Request $request): Response
    {
        return new Response('TestController1::action1');
    }

    #[Route('/action2', methods: ['POST'], name: 'test1_action2')]
    public function action2(Request $request): Response
    {
        return new Response('TestController1::action2');
    }
}
