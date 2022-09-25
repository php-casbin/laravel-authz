<?php

namespace Lauthz\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Casbin\Enforcer
 * @method static string[] getRolesForUser(string $name, string ...$domain)
 * @method static string[] getUsersForRole(string $name, string ...$domain)
 * @method static bool hasRoleForUser(string $name, string $role, string ...$domain)
 * @method static bool addRoleForUser(string $user, string $role, string ...$domain)
 * @method static bool addRolesForUser(string $user, array $roles, string ...$domain)
 * @method static bool deleteRoleForUser(string $user, string $role, string ...$domain)
 * @method static bool deleteRolesForUser(string $user, string ...$domain)
 * @method static bool deleteUser(string $user)
 * @method static bool deleteRole(string $role)
 * @method static bool deletePermission(string ...$permission)
 * @method static bool addPermissionForUser(string $user, string ...$permission)
 * @method static bool addPermissionsForUser(string $user, array ...$permissions)
 * @method static bool deletePermissionForUser(string $user, string ...$permission)
 * @method static bool deletePermissionsForUser(string $user)
 * @method static array getPermissionsForUser(string $user, string ...$domain)
 * @method static bool hasPermissionForUser(string $user, string ...$permission)
 * @method static array getImplicitRolesForUser(string $name, string ...$domain)
 * @method static array getImplicitUsersForRole(string $name, string ...$domain)
 * @method static array getImplicitResourcesForUser(string $user, string ...$domain)
 * @method static array getImplicitPermissionsForUser(string $user, string ...$domain)
 * @method static array getImplicitUsersForPermission(string ...$permission)
 * @method static string[] getAllUsersByDomain(string $domain)
 * @method static array getUsersForRoleInDomain(string $name, string $domain)
 * @method static array getRolesForUserInDomain(string $name, string $domain)
 * @method static array getPermissionsForUserInDomain(string $name, string $domain)
 * @method static bool addRoleForUserInDomain(string $user, string $role, string $domain)
 * @method static bool deleteRoleForUserInDomain(string $user, string $role, string $domain)
 * @method static bool deleteRolesForUserInDomain(string $user, string $domain)
 * @method static bool deleteAllUsersByDomain(string $domain)
 * @method static bool deleteDomains(string ...$domains)
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
