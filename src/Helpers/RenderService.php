<?php

namespace App\Helpers;

use Routing\Router;
use Templates\HomeTemplate;
use Templates\LoginTemplate;

/**
 * Classe RenderService
 * Gère le rendu des templates.
 */
class RenderService
{
    /**
     * Rend un template spécifique avec les données fournies.
     *
     * @param string $templateName Le nom du template à rendre (ex: "Home", "Login").
     * @param array $datas Les données à passer au template.
     * @return void
     */
    public function render(string $templateName, array $datas = []): void
    {
        $className = '\\Templates\\' . ucfirst($templateName) . 'Template';
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