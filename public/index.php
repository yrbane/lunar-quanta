<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Lunar\Service\Core\FrontController;

$frontController = new FrontController();
$frontController->run();

dump('TOTO');
dump(33);
dump($frontController);
dump($_SERVER);
