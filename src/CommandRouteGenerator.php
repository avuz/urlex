<?php

namespace Uzbek\Urlex;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;

class CommandRouteGenerator extends Command
{
    protected $signature = 'urlex:generate {path=./resources/assets/js/urlex.js} {--url=/}';

    protected $description = 'Generate js file for including in build process';

    protected $baseUrl;
    protected $baseProtocol;
    protected $baseDomain;
    protected $basePort;
    protected $router;

    public function __construct(Router $router, Filesystem $files)
    {
        parent::__construct();

        $this->router = $router;
        $this->files = $files;
    }

    public function handle()
    {
        $path = $this->argument('path');

        $generatedRoutes = $this->generate();

        $this->makeDirectory($path);

        $this->files->put($path, $generatedRoutes);
    }

    public function generate($group = false)
    {
        $this->prepareDomain();

        $json = $this->getRoutePayload($group)->toJson();

        $defaultParameters = method_exists(app('url'), 'getDefaultParameters') ? json_encode(app('url')->getDefaultParameters()) : '[]';

        return <<<EOT
    var Urlex = {
        namedRoutes: $json,
        baseUrl: '{$this->baseUrl}',
        baseProtocol: '{$this->baseProtocol}',
        baseDomain: '{$this->baseDomain}',
        basePort: {$this->basePort},
        defaultParameters: $defaultParameters
    };

    if (typeof window.Urlex !== 'undefined') {
        for (var name in window.Urlex.namedRoutes) {
            Urlex.namedRoutes[name] = window.Urlex.namedRoutes[name];
        }
    }

    export {
        Urlex
    }

EOT;
    }

    private function prepareDomain()
    {
        $url = url($this->option('url'));
        $parsedUrl = parse_url($url);

        $this->baseUrl = $url . '/';
        $this->baseProtocol = array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'http';
        $this->baseDomain = array_key_exists('host', $parsedUrl) ? $parsedUrl['host'] : '';
        $this->basePort = array_key_exists('port', $parsedUrl) ? $parsedUrl['port'] : 'false';
    }

    public function getRoutePayload($group = false)
    {
        return RoutePayload::compile($this->router, $group);
    }

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
        return $path;
    }
}
