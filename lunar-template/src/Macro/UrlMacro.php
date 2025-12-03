<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace Lunar\Template\Macro;

class UrlMacro implements MacroInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getName(): string
    {
        return 'url';
    }

    /**
     * @param array<int, mixed> $args
     */
    public function execute(array $args)
    {
        $routeName = $args[0] ?? '';
        $paramsJson = $args[1] ?? '{}';

        $route = $this->router->getRouteByName($routeName);
        if (!$route) {
            return '#ROUTE '.$routeName.' NOT FOUND !!!';
        }

        $url = $route['path'];
        $params = json_decode($paramsJson, true);
        if (!is_array($params)) {
            $params = [];
        }
        if (!empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }
}
