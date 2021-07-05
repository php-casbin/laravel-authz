<h1 align="center">
    Laravel Authorization
</h1>

<p align="center">
    <strong>Laravel-authz 是一个专为 Laravel 打造的授权（角色和权限控制）工具</strong>    
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
[英文版本](https://github.com/php-casbin/laravel-authz/blob/master/README.md)

它基于 [PHP-Casbin](https://github.com/php-casbin/php-casbin), 一个强大的、高效的开源访问控制框架，支持基于`ACL`, `RBAC`, `ABAC`等访问控制模型。

在这之前，你需要先了解 [Casbin](https://github.com/php-casbin/php-casbin) 的相关知识。

* [安装](#安装)
* [用法](#用法)
  * [快速开始](#快速开始)
  * [使用 Enforcer Api](#使用-enforcer-api)
  * [使用中间件](#使用中间件)
    * [基础 Enforcer 中间件](#基础-enforcer-中间件)
    * [HTTP 请求中间件 ( 同时支持RESTful )](#http-请求中间件--同时支持-restful-)
  * [多个 Enforcer 设置](#多个-Enforcer-设置)
  * [使用 artisan 命令](#使用-artisan-命令)
  * [缓存](#使用缓存)
* [感谢](#感谢)
* [License](#license)

## 安装

在 Laravel 应用根目录下的 `composer.json` 文件中指定该扩展，然后运行下面的 `composer` 命令。该扩展会被下载

```
composer install
```

或者使用命令行工具进入 Laravel 应用的根目录，运行下面的 `composer` 命令来直接安装该扩展

```php
composer require casbin/laravel-authz
```

`Lauthz\LauthzServiceProvider` 默认会被自动发现并注册，但你也可以像下面这样手动注册它

在 `config/app.php` 文件中添加该服务提供者

```php
'providers' => [
    /*
     * Package Service Providers...
     */
    Lauthz\LauthzServiceProvider::class,
]
```

`Enforcer` 门面也会被自动发现，但你也可以像下面这样手动添加它

在 `config/app.php` 文件中添加该门面

```php
'aliases' => [
    // ...
    'Enforcer' => Lauthz\Facades\Enforcer::class,
]
```

如果想要发布该扩展的设置文件，运行下面的 `artisan` 命令

```
php artisan vendor:publish
```

这会在 Laravel 的 `config/` 目录下生产一个叫做 `lauthz-rbac-model.conf` 的模型设置文件，和一个叫做 `lauthz.php` 的扩展设置文件


如果想要创建扩展对应的数据库文件，运行下面的 `artisan` 命令

```
php artisan migrate
```

这会创建一个叫做 `rules` 的数据表


## 用法

### 快速开始

安装成功后，可以这样使用:

```php
use Enforcer;

// adds permissions to a user
Enforcer::addPermissionForUser('eve', 'articles', 'read');
// adds a role for a user.
Enforcer::addRoleForUser('eve', 'writer');
// adds permissions to a rule
Enforcer::addPolicy('writer', 'articles','edit');

```

你可以检查一个用户是否拥有某个权限:

```php
// to check if a user has permission
if (Enforcer::enforce("eve", "articles", "edit")) {
    // permit eve to edit articles
} else {
    // deny the request, show an error
}

```

### 使用 Enforcer Api

它提供了非常丰富的 `API`，以促进对 `Policy` 的各种操作：

获取所有角色:

```php
Enforcer::getAllRoles(); // ['writer', 'reader']
```

获取所有的角色的授权规则：

```php
Enforcer::getPolicy();
```

获取某个用户的所有角色：

```php
Enforcer::getRolesForUser('eve'); // ['writer']
```

获取担任某个角色的所有用户：

```php
Enforcer::getUsersForRole('writer'); // ['eve']
```

决定用户是否拥有某个角色：

```php
Enforcer::hasRoleForUser('eve', 'writer'); // true or false
```

给用户添加角色：

```php
Enforcer::addRoleForUser('eve', 'writer');
```

赋予权限给某个用户或角色：

```php
// to user
Enforcer::addPermissionForUser('eve', 'articles', 'read');
// to role
Enforcer::addPermissionForUser('writer', 'articles','edit');
```

删除用户的角色：

```php
Enforcer::deleteRoleForUser('eve', 'writer');
```

删除某个用户的所有角色：

```php
Enforcer::deleteRolesForUser('eve');
```

删除单个角色：

```php
Enforcer::deleteRole('writer');
```

删除某个权限：

```php
Enforcer::deletePermission('articles', 'read'); // returns false if the permission does not exist (aka not affected).
```

删除某个用户或角色的权限：

```php
Enforcer::deletePermissionForUser('eve', 'articles', 'read');
```

删除某个用户或角色的所有权限：

```php
// to user
Enforcer::deletePermissionsForUser('eve');
// to role
Enforcer::deletePermissionsForUser('writer');
```

获取用户或角色的所有权限：

```php
Enforcer::getPermissionsForUser('eve'); // return array
```

决定某个用户是否拥有某个权限：

```php
Enforcer::hasPermissionForUser('eve', 'articles', 'read');  // true or false
```

更多 `API` 参考 [Casbin API](https://casbin.org/docs/en/management-api) 。

### 使用中间件

这个扩展包括 `EnforcerMiddleware`，`RequestMiddleware` 这两个中间件，你可以在你 Laravel 应用的 `app/Http/Kernel.php` 文件中添加上它们

```php
protected $routeMiddleware = [
    // ...
    // a basic Enforcer Middleware
    'enforcer' => \Lauthz\Middlewares\EnforcerMiddleware::class,
    // an HTTP Request Middleware
    'http_request' => \Lauthz\Middlewares\RequestMiddleware::class,
];
```

#### 基础 Enforcer 中间件

然后，你可以通过使用该中间件来保护对应的路由

```php
Route::group(['middleware' => ['enforcer:articles,read']], function () {
    // pass
});
```

#### HTTP 请求中间件 ( 同时支持 RESTful )

如果你想要认证一个请求，你需要首先在 `config/lauthz-rbac-model.conf` 文件中定义相应的模型设置

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

然后，向该请求对应的路由添加中间件规则

```php
Route::group(['middleware' => ['http_request']], function () {
    Route::resource('photo', 'PhotoController');
});
```

### 多个 Enforcer 设置

如果在你的项目中你需要多种不同的权限控制，你可以添加多个 `Enforcer` 设置来实现

在该扩展的 `config/lauthz.php` 设置文件中，内容应该类似下面这样

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

然后，你可以像下面这样来选择使用哪一个 `Enforcer` 设置

```php
Enforcer::guard('second')->enforce("eve", "articles", "edit");
```


### 使用 artisan 命令

你可以在命令行中通过 artisan 命令来创建一个授权策略

为用户添加权限

```bash
php artisan policy:add eve,articles,read
```

为角色添加权限

```bash
php artisan policy:add writer,articles,edit
```

为指定用户添加角色

```bash
php artisan role:assign eve writer
```

### 使用缓存

可以通过缓存授权规则来提升应用的执行速度，这一功能默认是关闭的

在 Laravel 应用的 `config/lauthz.php` 文件中添加你自己的缓存设置

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

## 感谢

[Casbin](https://github.com/php-casbin/php-casbin)，你可以在其 [官网](https://casbin.org/) 上查看全部文档。

## License

This project is licensed under the [Apache 2.0 license](LICENSE).