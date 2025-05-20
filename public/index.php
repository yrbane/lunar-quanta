<?php
declare(strict_types=1);

require_once '../vendor/autoload.php';

use App\Service\Core\FrontController;

$frontController = new FrontController();
dump($_SERVER);
$frontController->run();
