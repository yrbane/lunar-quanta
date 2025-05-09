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
use App\Service\Core\Http\HttpStatus;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;

/**
 * Class ErrorController.
 *
 * Gère l'affichage des pages d'erreur en fonction des codes HTTP.
 */
class ErrorController extends BaseController
{
    /**
     * Affiche une page d'erreur.
     *
     * @param Request     $request L'objet Request
     * @param int         $code    le code HTTP de l'erreur (par défaut 404)
     * @param null|string $message message d'erreur optionnel
     *
     * @return Response la réponse HTTP générée
     */
    #[Route('/error', methods: ['GET'], name: 'error')]
    public function index(Request $request, int $code = HttpStatus::NOT_FOUND, ?string $message = null): Response
    {
        $errorMessage = $message ?? HttpStatus::getDefaultMessage($code);

        $html = $this->render('error.html', [
            'title' => 'Error '.$code,
            'errorCode' => $code,
            'errorMessage' => $errorMessage,
        ]);

        return new Response($html, $code);
    }
}
