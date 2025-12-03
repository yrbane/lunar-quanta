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

namespace Lunar\Controller;

use Lunar\Attribute\Route;
use Lunar\Service\Core\BaseController;
use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;

/**
 * Class DefaultController.
 *
 * Controller for handling the home page and testing errors.
 */
class DefaultController extends BaseController
{
    /**
     * Displays the homepage.
     *
     * @param Request $request the HTTP request object
     *
     * @return Response the HTTP response containing the home page
     */
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $html = $this->render('examples/blog.html', [
            'title' => 'Accueil',
            'message' => 'Bienvenue sur notre site !',
            'loginUrl' => '/login',
        ]);

        return new Response($html);
    }
}
