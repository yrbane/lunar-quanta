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

namespace App\Controller;

use App\Attribute\Route;
use App\Service\Core\BaseController;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;

/**
 * Contrôleur TestController généré automatiquement.
 */
class TestController extends BaseController
{
    /**
     * Affiche la page index.
     */
    #[Route(path: '/test', methods: ['GET'], name: 'test.index')]
    public function index(Request $request): Response
    {
        // TODO : implémenter la logique de l'action index
        $html = $this->render('test/index', []);

        return new Response($html);
    }

    /**
     * Triggers a 500 error for testing purposes.
     *
     * Cette méthode lance délibérément une exception pour simuler une erreur 500.
     *
     * @param Request $request the HTTP request object
     *
     * @return Response this response should not be reached normally
     *
     * @throws \RuntimeException intentionally thrown to simulate a server error
     */
    #[Route('/test500', methods: ['GET'], name: 'test.500')]
    public function test500(Request $request): Response
    {
        // Lancer une exception pour simuler une erreur interne (500)
        throw new \RuntimeException('Intentional error to test HTTP 500 handling.');
    }

    /**
     * @throws \Exception
     */
    #[Route('/testcss', methods: ['GET'], name: 'test.css')]
    public function testcss(Request $request): Response
    {
        $html = $this->render('test/css.html', []);

        return new Response($html);
    }

    #[Route('/testflex', methods: ['GET'], name: 'test.flex')]
    public function testflex(Request $request): Response
    {
        $html = $this->render('test/flex.html', []);

        return new Response($html);
    }
}
