<?php

namespace Uzbek\Urlex;

use Illuminate\Routing\Router;

class RoutePayload
{
    protected $routes;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->routes = $this->nameKeyedRoutes();
    }

    public static function compile(Router $router, $group = false)
    {
        return (new static($router))->applyFilters($group);
    }

    public function applyFilters($group)
    {
        if ($group) {
            return $this->group($group);
        }

        // return unfiltered routes if user set both config options.
        if (config()->has('urlex.blacklist') && config()->has('urlex.whitelist')) {
            return $this->routes;
        }

        if (config()->has('urlex.blacklist')) {
            return $this->blacklist();
        }

        if (config()->has('urlex.whitelist')) {
            return $this->whitelist();
        }

        return $this->routes;
    }

    public function group($group)
    {
        if (is_array($group)) {
            $filters = [];
            foreach ($group as $groupName) {
                $filters = array_merge($filters, config("urlex.groups.{$groupName}"));
            }

            return is_array($filters) ? $this->filter($filters, true) : $this->routes;
        } else if (config()->has("urlex.groups.{$group}")) {
            return $this->filter(config("urlex.groups.{$group}"), true);
        }

        return $this->routes;
    }

    public function blacklist()
    {
        return $this->filter(config('urlex.blacklist'), false);
    }

    public function whitelist()
    {
        return $this->filter(config('urlex.whitelist'), true);
    }

    public function filter($filters = [], $include = true)
    {
        return $this->routes->filter(function ($route, $name) use ($filters, $include) {
            foreach ($filters as $filter) {
                if (str_is($filter, $name)) {
                    return $include;
                }
            }

            return !$include;
        });
    }

    protected function nameKeyedRoutes()
    {
        return collect($this->router->getRoutes()->getRoutesByName())
            ->map(function ($route) {
                if ($this->isListedAs($route, 'blacklist')) {
                    $this->appendRouteToList($route->getName(), 'blacklist');
                } elseif ($this->isListedAs($route, 'whitelist')) {
                    $this->appendRouteToList($route->getName(), 'whitelist');
                }
                if (config()->has('urlex.blacklist') && config()->has('urlex.whitelist')) {
                    return $this->routes;
                }
                return collect($route)->only(['uri', 'methods'])
                    ->put('domain', $route->domain())
                    ->when($middleware = config('urlex.middleware'), function ($collection) use ($middleware, $route) {
                        if (config()->has('urlex.middleware_except')) {
                            $except = config('urlex.middleware_except');
                            return $collection->put('middleware', collect($route->middleware())->diff($except)->values());
                        } else {
                            if (is_array($middleware)) {
                                return $collection->put('middleware', collect($route->middleware())->intersect($middleware)->values());
                            }
                        }
                        return $collection->put('middleware', $route->middleware());
                    });
            });
    }

    protected function appendRouteToList($name, $list)
    {
        config()->push("urlex.{$list}", $name);
    }

    protected function isListedAs($route, $list)
    {
        return (isset($route->listedAs) && $route->listedAs === $list)
            || array_get($route->getAction(), 'listed_as', null) === $list;
    }
}
