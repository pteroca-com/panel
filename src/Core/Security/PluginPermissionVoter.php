<?php

namespace App\Core\Security;

use App\Core\Service\Security\PermissionRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

/**
 * Symfony Voter for plugin-registered custom permissions.
 *
 * Integrates PermissionRegistry with Symfony's security system.
 * Checks permissions registered by plugins via PermissionsRegisteredEvent.
 *
 * Voting Strategy:
 * - If permission is registered in PermissionRegistry: Vote ACCESS_GRANTED or ACCESS_DENIED
 * - If permission is not registered: Vote ACCESS_ABSTAIN (let other voters decide)
 *
 * Permission Check Logic:
 * 1. Check if user has any of the required roles
 * 2. If customChecker is provided, execute custom logic
 * 3. Grant access if either check passes
 */
class PluginPermissionVoter extends Voter
{
    /**
     * Prefix for plugin permissions to avoid conflicts with core permissions.
     */
    private const PLUGIN_PERMISSION_PREFIX = 'PLUGIN_';

    public function __construct(
        private readonly PermissionRegistry $permissionRegistry,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Determine if this voter supports the given attribute.
     *
     * Only supports attributes (permissions) that:
     * - Start with PLUGIN_ prefix
     * - Are registered in PermissionRegistry
     *
     * @param string $attribute Permission to check
     * @param mixed $subject Subject being voted on (typically null for permissions)
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Only handle plugin permissions
        if (!str_starts_with($attribute, self::PLUGIN_PERMISSION_PREFIX)) {
            return false;
        }

        // Only handle permissions registered in registry
        return $this->permissionRegistry->hasPermission($attribute);
    }

    /**
     * Vote on whether user has the given permission.
     *
     * @param string $attribute Permission to check
     * @param mixed $subject Subject being voted on
     * @param TokenInterface $token Security token containing user
     * @return bool True if access granted, false if denied
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be authenticated
        if (!$user instanceof UserInterface) {
            $this->logger->debug("Permission denied: user not authenticated", [
                'permission' => $attribute,
            ]);
            return false;
        }

        $permission = $this->permissionRegistry->getPermission($attribute);

        if ($permission === null) {
            // This shouldn't happen due to supports() check, but be defensive
            $this->logger->warning("Permission not found in registry", [
                'permission' => $attribute,
            ]);
            return false;
        }

        // Check 1: Required roles
        $requiredRoles = $permission['requiredRoles'] ?? [];
        $userRoles = $user->getRoles();

        $hasRequiredRole = false;
        if (empty($requiredRoles)) {
            // No specific roles required - allow all authenticated users
            $hasRequiredRole = true;
        } else {
            // Check if user has any of the required roles
            foreach ($requiredRoles as $role) {
                if (in_array($role, $userRoles, true)) {
                    $hasRequiredRole = true;
                    break;
                }
            }
        }

        // Check 2: Custom checker (if provided)
        $customChecker = $permission['customChecker'] ?? null;
        $customCheckPassed = false;

        if ($customChecker !== null && is_callable($customChecker)) {
            try {
                $customCheckPassed = (bool) $customChecker($user, $subject, $attribute);
            } catch (Throwable $e) {
                $this->logger->error("Custom permission checker threw exception", [
                    'permission' => $attribute,
                    'exception' => $e->getMessage(),
                ]);
                $customCheckPassed = false;
            }
        }

        // Grant access if either check passed
        $granted = $hasRequiredRole || $customCheckPassed;

        $this->logger->debug("Permission check completed", [
            'permission' => $attribute,
            'user' => $user->getUserIdentifier(),
            'required_roles' => $requiredRoles,
            'user_roles' => $userRoles,
            'has_required_role' => $hasRequiredRole,
            'custom_check_passed' => $customCheckPassed,
            'granted' => $granted,
        ]);

        return $granted;
    }
}
