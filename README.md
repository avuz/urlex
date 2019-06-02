# Urlex - Use your Laravel Named Routes inside JavaScript


Urlex creates a Blade directive which you can include in your views. This will export a JavaScript object of your application's named routes, keyed by their names (aliases), as well as a global `route()` helper function which you can use to access your routes in your JavaScript.

## Installation

1. Add Urlex to your Composer file: `composer require uzbek/urlex`

2. (if Laravel 5.4) Add `Uzbek\Urlex\UrlexServiceProvider::class` to the `providers` array in your `config/app.php`.

3. Include our Blade Directive (`@routes`) somewhere in your template before your main application JavaScript is loaded&mdash;likely in the header somewhere.

## Usage

This package replaces the `@routes` directive with a collection of all of your application's routes, keyed by their names. This collection is available at `Urlex.namedRoutes`.

### Examples:

Coming soon

### Default Values
See Laravel [documentation](https://laravel.com/docs/5.5/urls#default-values)

Default values work out of the box for Laravel versions >= 5.5.29,
for the previous versions you will need to set the default parameters
by including this code somewhere in the same page as our Blade Directive (@routes)
```js
Urlex.defaultParameters = {
    //example
    locale: "en"
}
```

## Filtering Routes
Filtering routes is *completely* optional. If you want to pass all of your routes to JavaScript by default, you can carry on using Urlex as described above.

### Basic Whitelisting & Blacklisting
To take advantage of basic whitelisting or blacklisting of routes, you will first need to create a standard config file called `Urlex.php` in the `config/` directory of your Laravel app and set **either** the `whitelist` or `blacklist` setting to an array of route names.

**Note: You've got to choose one or the other. Setting `whitelist` and `blacklist` will disable filtering altogether and simply return the default list of routes.**

#### Example `config/Urlex.php`:
```php
<?php
return [
    // 'whitelist' => ['home', 'api.*'],
    'blacklist' => ['debugbar.*', 'horizon.*', 'admin.*'],
];
```

As shown in the example above, Urlex the use of asterisks as wildcards in filters. `home` will only match the route named `home` whereas `api.*` will match any route whose name begins with `api.`, such as `api.posts.index` and `api.users.show`.

### Simple Whitelisting & Blacklisting Macros

Whitelisting and blacklisting can also be achieved using the following macros.

#### Example Whitelisting

```php
Route::whitelist(function () {
    Route::get('...')->name('posts');
});

Route::whitelist()->get('...')->name('posts');
```

#### Example Blacklisting

```php
Route::blacklist(function () {
    Route::get('...')->name('posts');
});

Route::blacklist()->get('...')->name('posts');
```

### Advanced Whitelisting Using Groups

You may also optionally define multiple whitelists by defining `groups` in your `config/urlex.php`:

```php
<?php
return [
    'groups' => [
        'admin' => [
            'admin.*',
            'posts.*',
        ],
        'author' => [
            'posts.*',
        ]
    ],
];
```

In the above example, you can see we have configured multiple whitelists for different user roles.  You may expose a specific whitelist group by passing the group key into `@routes` within your blade view.  Example:

```php
@routes('author')
```
**Note: Using a group will always take precedence over the above mentioned `whitelist` and `blacklist` settings.**

## Artisan command

Urlex publishes an artisan command to generate a `urlex.js` routes file, which can be used as part of an asset pipeline such as [Laravel Mix](https://laravel.com/docs/mix).

You can run `php artisan urlex:generate` in your project to generate a static routes file in `resources/assets/js/Urlex.js`.

Optionally, include a second parameter to override the path and file names (you must pass a file name with the path):

```
php artisan urlex:generate "resources/foo.js"
```

Example `urlex.js`, where the named routes `home` and `login` exist in `routes/web.php`:

```php
// routes/web.php

<?php

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', function () {
    return view('login');
})->name('login');
```

```js
// urlex.js

var Urlex = {
    namedRoutes: {"home":{"uri":"\/","methods":["GET","HEAD"],"domain":null},"login":{"uri":"login","methods":["GET","HEAD"],"domain":null}},
    baseUrl: 'http://myapp.local/',
    baseProtocol: 'http',
    baseDomain: 'myapp.local',
    basePort: false
};

export {
    Urlex
}
```

## Contributions & Credits


