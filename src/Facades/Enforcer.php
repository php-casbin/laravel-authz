<?php

namespace Lauthz\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Casbin\Enforcer
 * @method static list<string> getAllRoles()
 * @method static list<string> getRolesForUser(string $name, string ...$domain)
 * @method static list<string> getUsersForRole(string $name, string ...$domain)
 * @method static bool hasRoleForUser(string $name, string $role, string ...$domain)
 * @method static bool addRoleForUser(string $user, string $role, string ...$domain)
 * @method static bool addRolesForUser(string $user, list<string> $roles, string ...$domain)
 * @method static bool deleteRoleForUser(string $user, string $role, string ...$domain)
 * @method static bool deleteRolesForUser(string $user, string ...$domain)
 * @method static bool deleteUser(string $user)
 * @method static bool deleteRole(string $role)
 * @method static bool deletePermission(string ...$permission)
 * @method static bool addPermissionForUser(string $user, string ...$permission)
 * @method static bool addPermissionsForUser(string $user, list<string> ...$permissions)
 * @method static bool deletePermissionForUser(string $user, string ...$permission)
 * @method static bool deletePermissionsForUser(string $user)
 * @method static list<list<string>> getPermissionsForUser(string $user, string ...$domain)
 * @method static bool hasPermissionForUser(string $user, string ...$permission)
 * @method static list<string> getImplicitRolesForUser(string $name, string ...$domain)
 * @method static list<string> getImplicitUsersForRole(string $name, string ...$domain)
 * @method static list<string> getImplicitResourcesForUser(string $user, string ...$domain)
 * @method static list<list<string>> getImplicitPermissionsForUser(string $user, string ...$domain)
 * @method static list<list<string>> getImplicitUsersForPermission(string ...$permission)
 * @method static list<string> getAllUsersByDomain(string $domain)
 * @method static list<string> getUsersForRoleInDomain(string $name, string $domain)
 * @method static list<string> getRolesForUserInDomain(string $name, string $domain)
 * @method static list<list<string>> getPermissionsForUserInDomain(string $name, string $domain)
 * @method static bool addRoleForUserInDomain(string $user, string $role, string $domain)
 * @method static bool deleteRoleForUserInDomain(string $user, string $role, string $domain)
 * @method static bool deleteRolesForUserInDomain(string $user, string $domain)
 * @method static bool deleteAllUsersByDomain(string $domain)
 * @method static bool deleteDomains(string ...$domains)
 *
 * @mixin \Lauthz\EnforcerManager
 */
class Enforcer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'enforcer';
    }
}
