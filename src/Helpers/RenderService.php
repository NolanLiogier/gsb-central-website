<?php

namespace App\Helpers;

use Routing\Router;
use Templates\HomeTemplate;
use Templates\LoginTemplate;

class RenderService
{
    public function render(string $templateName, array $datas = []): void
    {
        $className = '\\Templates\\' . ucfirst($templateName);

        $methodName = 'display' . ucfirst($templateName);

        if (!class_exists($className)) {
            $_SESSION['error_message'] = "Template class not found: " . $className;
            $router = new Router();
            $router->getRoute();
            return;
        }

        if (!method_exists($className, $methodName) || !is_callable([$className, $methodName])) {
            $_SESSION['error_message'] = "Template method not found: " . $methodName;
            $router = new Router();
            $router->getRoute();
            return;
        }

        $html = $className::$methodName($datas);
        echo $html;
    }
}