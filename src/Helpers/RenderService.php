<?php

namespace App\Helpers;

use Routing\Router;
use Templates\BaseTemplate;

/**
 * Classe RenderService
 * Gère le rendu des templates de manière sécurisée et efficace.
 */
class RenderService
{

    private BaseTemplate $baseTemplate;

    public function __construct()
    {
        $this->baseTemplate = new BaseTemplate();
    }
    /**
     * Rend un template spécifique avec les données fournies.
     *
     * @param string $templateName Le nom du template à rendre (ex: "Home", "User").
     * @param array $datas Les données à passer au template.
     * @return void
     * @throws \Exception Si le template n'existe pas ou ne peut pas être rendu.
     */
    public function displayTemplates(string $templateName, array $datas = [], string $templateTitle = ''): void
    {
        try {
            if (empty($templateName) || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $templateName)) {
                throw new \Exception("Invalid template name: " . $templateName);
            }

            $content = $this->getTemplateContent($templateName, $datas);
            echo $this->baseTemplate->render( $templateTitle,  $content,  '/' . $templateName);
            exit();

        } catch (\Exception $e) {
            $this->handleRenderError($e->getMessage());
        }
    }

    /**
     * Rend le template avec les données fournies.
     *
     * @param string $templateName Le nom du template à rendre.
     * @param array $datas Les données à passer au template.
     * @return string Le HTML rendu.
     * @throws \Exception Si le template ne peut pas être rendu.
     */
    private function getTemplateContent(string $templateName, array $datas): string
    {
        $className = 'Templates\\' . ucfirst($templateName) . 'Template';
        $methodName = 'display' . ucfirst($templateName);

        if (!class_exists($className)) {
            throw new \Exception("Template class not found: " . $className);
        }

        if (!method_exists($className, $methodName)) {
            throw new \Exception("Template method not found: " . $methodName);
        }

        $templateInstance = new $className();
        if (!is_callable([$templateInstance, $methodName])) {
            throw new \Exception("Template method not callable: " . $methodName);
        }

        $result = $templateInstance->$methodName($datas);
        
        if (!is_string($result)) {
            throw new \Exception("Template method must return a string");
        }

        return $result;
    }

    /**
     * Gère les erreurs de rendu.
     *
     * @param string $errorMessage Le message d'erreur.
     * @return void
     */
    private function handleRenderError(string $errorMessage): void
    {
        // Afficher une erreur simple au lieu de rediriger pour éviter les boucles infinies
        http_response_code(500);
        echo "<!DOCTYPE html>
            <html>
            <head>
                <title>Erreur</title>
                <meta charset='UTF-8'>
            </head>
            <body>
                <h1>Erreur de rendu</h1>
                <p>Une erreur s'est produite lors du rendu de la page : " . htmlspecialchars($errorMessage) . "</p>
                <p><a href='/user'>Retour à la connexion</a></p>
            </body>
            </html>";
        exit();
    }
}