<h1 align="center">
    Laravel Authorization
</h1>

<p align="center">
    <strong>Laravel-authz is an authorization library for the laravel framework.</strong>    
</p>

<p align="center">
    <a href="https://github.com/php-casbin/laravel-authz/actions">
        <img src="https://github.com/php-casbin/laravel-authz/workflows/build/badge.svg?branch=master" alt="Build Status">
    </a>
    <a href="https://coveralls.io/github/php-casbin/laravel-authz">
        <img src="https://coveralls.io/repos/github/php-casbin/laravel-authz/badge.svg" alt="Coverage Status">
    </a>
    <a href="https://packagist.org/packages/casbin/laravel-authz">
        <img src="https://poser.pugx.org/casbin/laravel-authz/v/stable" alt="Latest Stable Version">
    </a>
     <a href="https://packagist.org/packages/casbin/laravel-authz">
        <img src="https://poser.pugx.org/casbin/laravel-authz/downloads" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/casbin/laravel-authz">
        <img src="https://poser.pugx.org/casbin/laravel-authz/license" alt="License">
    </a>
</p>

It's based on [Casbin](https://github.com/php-casbin/php-casbin), an authorization library that supports access control models like ACL, RBAC, ABAC.

All you need to learn to use `Casbin` first.

* [Installation](#installation)
* [Usage](#usage)
  * [Quick start](#quick-start)
  * [Using Enforcer Api](#using-enforcer-api)
  * [Using a middleware](#using-a-middleware)
    * [basic Enforcer Middleware](#basic-enforcer-middleware)
    * [HTTP Request Middleware ( RESTful is also supported )](#http-request-middleware--restful-is-also-supported-)
  * [Multiple enforcers](#multiple-enforcers)
  * [Using artisan commands](#using-artisan-commands)
  * [Cache](#using-cache)
* [Thinks](#thinks)
* [License](#license)

## Installation

Require this package in the `composer.json` of your Laravel project. This will download the package.

```
composer require casbin/laravel-authz
```

The `Lauthz\LauthzServiceProvider` is `auto-discovered` and registered by default, but if you want to register it yourself:

Add the ServiceProvider in `config/app.php`

```php
'providers' => [
    /*
     * Package Service Providers...
     */
    Lauthz\LauthzServiceProvider::class,
]
```

The Enforcer facade is also `auto-discovered`, but if you want to add it manually:

Add the Facade in `config/app.php`

```php
'aliases' => [
    // ...
    'Enforcer' => Lauthz\Facades\Enforcer::class,
]
```

To publish the config, run the vendor publish command:

```
php artisan vendor:publish
```

This will create a new model config file named `config/lauthz-rbac-model.conf` and a new lauthz config file named `config/lauthz.php`.


To migrate the migrations, run the migrate command:

```
php artisan migrate
```

This will create a new table named `rules`


## Usage

### Quick start

Once installed you can do stuff like this:

```php

use Enforcer;

// adds permissions to a user
Enforcer::addPermissionForUser('eve', 'articles', 'read');
// adds a role for a user.
Enforcer::addRoleForUser('eve', 'writer');
// adds permissions to a role
Enforcer::addPolicy('writer', 'articles','edit');

```

You can check if a user has a permission like this:

```php
// to check if a user has permission
if (Enforcer::enforce("eve", "articles", "edit")) {
    // permit eve to edit articles
} else {
    // deny the request, show an error
}

```

### Using Enforcer Api

It provides a very rich api to facilitate various operations on the Policy:

Gets all roles:

```php
Enforcer::getAllRoles(); // ['writer', 'reader']
```

Gets all the authorization rules in the policy.:

```php
Enforcer::getPolicy();
```

Gets the roles that a user has.

```php
Enforcer::getRolesForUser('eve'); // ['writer']
```

Gets the users that has a role.

```php
Enforcer::getUsersForRole('writer'); // ['eve']
```

Determines whether a user has a role.

```php
Enforcer::hasRoleForUser('eve', 'writer'); // true or false
```

Adds a role for a user.

```php
Enforcer::addRoleForUser('eve', 'writer');
```

Adds a permission for a user or role.

```php
// to user
Enforcer::addPermissionForUser('eve', 'articles', 'read');
// to role
Enforcer::addPermissionForUser('writer', 'articles','edit');
```

Deletes a role for a user.

```php
Enforcer::deleteRoleForUser('eve', 'writer');
```

Deletes all roles for a user.

```php
Enforcer::deleteRolesForUser('eve');
```

Deletes a role.

```php
Enforcer::deleteRole('writer');
```

Deletes a permission.

```php
Enforcer::deletePermission('articles', 'read'); // returns false if the permission does not exist (aka not affected).
```

Deletes a permission for a user or role.

```php
Enforcer::deletePermissionForUser('eve', 'articles', 'read');
```

Deletes permissions for a user or role.

```php
// to user
Enforcer::deletePermissionsForUser('eve');
// to role
Enforcer::deletePermissionsForUser('writer');
```

Gets permissions for a user or role.

```php
Enforcer::getPermissionsForUser('eve'); // return array
```

Determines whether a user has a permission.

```php
Enforcer::hasPermissionForUser('eve', 'articles', 'read');  // true or false
```

See [Casbin API](https://casbin.org/docs/management-api#reference) for more APIs.

### Using a middleware

This package comes with `EnforcerMiddleware`, `RequestMiddleware` middlewares. You can add them inside your `app/Http/Kernel.php` file.

```php
protected $routeMiddleware = [
    // ...
    // a basic Enforcer Middleware
    'enforcer' => \Lauthz\Middlewares\EnforcerMiddleware::class,
    // an HTTP Request Middleware
    'http_request' => \Lauthz\Middlewares\RequestMiddleware::class,
];
```

#### basic Enforcer Middleware

Then you can protect your routes using middleware rules:

```php
Route::group(['middleware' => ['enforcer:articles,read']], function () {
    // pass
});
```

#### HTTP Request Middleware ( RESTful is also supported )

If you need to authorize a Request，you need to define the model configuration first in `config/lauthz-rbac-model.conf`:

```ini
[request_definition]
r = sub, obj, act

[policy_definition]
p = sub, obj, act

[role_definition]
g = _, _

[policy_effect]
e = some(where (p.eft == allow))

[matchers]
m = g(r.sub, p.sub) && keyMatch2(r.obj, p.obj) && regexMatch(r.act, p.act)
```

Then, using middleware rules:

```php
Route::group(['middleware' => ['http_request']], function () {
    Route::resource('photo', 'PhotoController');
});
```

### Multiple enforcers

If you need multiple permission controls in your project, you can configure multiple enforcers.

In the lauthz file, it should be like this:

```php
return [
    'default' => 'basic',

    'basic' => [
        'model' => [
            // ...
        ],

        'adapter' => Lauthz\Adapters\DatabaseAdapter::class,
        // ...
    ],

    'second' => [
        'model' => [
            // ...
        ],

        'adapter' => Lauthz\Adapters\DatabaseAdapter::class,
        // ...
    ],
];

```

Then you can choose which enforcers to use.

```php
Enforcer::guard('second')->enforce("eve", "articles", "edit");
```


### Using artisan commands

You can create a policy from a console with artisan commands.

To user:

```bash
php artisan policy:add eve,articles,read
```

To Role:

```bash
php artisan policy:add writer,articles,edit
```

Adds a role for a user:

```bash
php artisan role:assign eve writer
# Specify the ptype of the role assignment by using the --ptype option.
php artisan role:assign eve writer --ptype=g2
```

### Using cache

Authorization rules are cached to speed up performance. The default is off.

Sets your own cache configs in Laravel's `config/lauthz.php`. 

```php
'cache' => [
    // changes whether Lauthz will cache the rules.
    'enabled' => false,

    // cache store
    'store' => 'default',

    // cache Key
    'key' => 'rules',

    // ttl \DateTimeInterface|\DateInterval|int|null
    'ttl' => 24 * 60,
],
```

## Thinks

[Casbin](https://github.com/php-casbin/php-casbin) in Laravel. You can find the full documentation of Casbin [on the website](https://casbin.org/).

## License

This project is licensed under the [Apache 2.0 license](LICENSE).
