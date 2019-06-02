<?php

namespace Uzbek\Urlex;

use Illuminate\Routing\Router;
use function array_key_exists;

class BladeRouteGenerator
{
    private static $generated;
    private $baseDomain;
    private $basePort;
    private $baseUrl;
    private $baseProtocol;
    private $router;
    public  $routePayload;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getRoutePayload($group = false)
    {
        return RoutePayload::compile($this->router, $group);
    }

    public function generate($group = false)
    {
        $json = $this->getRoutePayload($group)->toJson();

        if (static::$generated) {
            return $this->generateMergeJavascript($json);
        }

        $this->prepareDomain();

        $defaultParameters = method_exists(app('url'), 'getDefaultParameters') ? json_encode(app('url')->getDefaultParameters()) : '[]';

        static::$generated = true;

        return <<<EOT
<script type="text/javascript">
    var Urlex = {
        namedRoutes: $json,
        baseUrl: '{$this->baseUrl}',
        baseProtocol: '{$this->baseProtocol}',
        baseDomain: '{$this->baseDomain}',
        basePort: {$this->basePort},
        defaultParameters: $defaultParameters
    };

</script>
EOT;
    }

    private function generateMergeJavascript($json)
    {
        return <<<EOT
<script type="text/javascript">
    (function() {
        var routes = $json;

        for (var name in routes) {
            Urlex.namedRoutes[name] = routes[name];
        }
    })();
</script>
EOT;
    }

    private function prepareDomain()
    {
        $url = url('/');
        $parsedUrl = parse_url($url);

        $this->baseUrl = $url . '/';
        $this->baseProtocol = array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'http';
        $this->baseDomain = array_key_exists('host', $parsedUrl) ? $parsedUrl['host'] : '';
        $this->basePort = array_key_exists('port', $parsedUrl) ? $parsedUrl['port'] : 'false';
    }
}
