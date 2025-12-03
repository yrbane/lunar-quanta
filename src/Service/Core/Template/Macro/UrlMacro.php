<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */

namespace Lunar\Service\Core\Template\Macro;

use Lunar\Service\Core\Router;

class UrlMacro implements MacroInterface
{
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

        $route = Router::getRouteByName($routeName);
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
