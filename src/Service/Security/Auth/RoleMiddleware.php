<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

use Lunar\Service\Core\Http\Request;
use Lunar\Service\Core\Http\Response;
use Lunar\Service\Core\Middleware\MiddlewareInterface;

/**
 * Middleware that requires specific roles.
 *
 * Blocks requests from users without the required roles.
 */
class RoleMiddleware implements MiddlewareInterface
{
    private Authenticator $authenticator;
    /** @var array<string> */
    private array $requiredRoles;
    private bool $requireAll;

    /**
     * @param Authenticator $authenticator The authenticator service
     * @param array<string> $requiredRoles The roles required to access the route
     * @param bool $requireAll If true, user must have ALL roles; if false, ANY role
     */
    public function __construct(
        Authenticator $authenticator,
        array $requiredRoles,
        bool $requireAll = false
    ) {
        $this->authenticator = $authenticator;
        $this->requiredRoles = $requiredRoles;
        $this->requireAll = $requireAll;
    }

    public function process(Request $request, callable $next): Response
    {
        $user = $this->authenticator->user();

        if (null === $user) {
            return new Response('Unauthorized', 401);
        }

        if (!$this->hasRequiredRoles($user)) {
            return new Response('Forbidden', 403);
        }

        return $next($request);
    }

    /**
     * Check if user has the required roles.
     */
    private function hasRequiredRoles(UserInterface $user): bool
    {
        $userRoles = $user->getRoles();

        if ($this->requireAll) {
            // User must have ALL required roles
            foreach ($this->requiredRoles as $role) {
                if (!in_array($role, $userRoles, true)) {
                    return false;
                }
            }
            return true;
        }

        // User must have ANY of the required roles
        foreach ($this->requiredRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
