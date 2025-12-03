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
use App\Entity\User;
use App\Service\Core\BaseController;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;
use App\Service\Storage\JsonStorage;

/**
 * Optionnel : Vous pouvez également annoter la classe pour définir un préfixe commun à toutes ses routes.
 *
 * #[Route('/user')]
 */
class UserController extends BaseController
{
    private JsonStorage $storage;

    public function __construct()
    {
        parent::__construct();
        $this->storage = new JsonStorage();
    }

    /**
     * Affiche le formulaire de création d'utilisateur ou traite sa soumission.
     *
     * L'attribut Route ci-dessous déclare que cette méthode doit être appelée pour la route "/user"
     * avec les méthodes HTTP GET et POST.
     *
     * @param Request $request la requête HTTP injectée
     *
     * @return Response la réponse HTTP générée
     */
    #[Route('/user', methods: ['GET', 'POST'], name: 'user.index')]
    public function index(Request $request): Response
    {
        if ('POST' === $request->getMethod()) {
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

            if ($email && $name && $password) {
                $user = new User($email, $name, $password);
                $this->storage->saveUser($user);

                // Rendu de la vue de confirmation via le moteur de template
                $html = $this->render('confirmation', [
                    'title' => 'User Created',
                    'content' => 'The user has been created successfully.',
                ]);

                return new Response($html);
            }
            $html = $this->render('error', [
                'title' => 'Invalid Input',
                'content' => 'Please check your input.',
            ]);

            return new Response($html);
        }

        // En GET, afficher le formulaire de création d'utilisateur
        $html = $this->render('user_form', [
            'title' => 'Create User',
        ]);

        return new Response($html);
    }
}
