<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict the request to users with one of the given roles.
     *
     * Usage: role:admin | role:admin,hr | role:candidate
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $allowedRoles = $this->resolveRoles($roles);

        if (! $user->hasAnyRole(...$allowedRoles)) {
            return response()->json([
                'message' => 'Forbidden. You do not have the required role to access this resource.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Convert middleware parameters into UserRole enum cases.
     *
     * @param  list<string>  $roles
     * @return list<UserRole>
     */
    private function resolveRoles(array $roles): array
    {
        $resolved = [];

        foreach ($roles as $role) {
            foreach (explode(',', $role) as $value) {
                $value = trim($value);

                if ($value === '') {
                    continue;
                }

                $enum = UserRole::tryFrom($value);

                if ($enum === null) {
                    throw new InvalidArgumentException(
                        "Invalid role [{$value}] configured on route middleware."
                    );
                }

                $resolved[] = $enum;
            }
        }

        if ($resolved === []) {
            throw new InvalidArgumentException(
                'At least one valid role must be provided to the role middleware.'
            );
        }

        return array_values(array_unique($resolved, SORT_REGULAR));
    }
}
