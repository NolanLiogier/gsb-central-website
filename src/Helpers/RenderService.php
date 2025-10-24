<?php

namespace App\Helpers;

use Routing\Router;

/**
 * Classe RenderService
 * Gère le rendu des templates de manière sécurisée et efficace.
 */
class RenderService
{
    /**
     * Rend un template spécifique avec les données fournies.
     *
     * @param string $templateName Le nom du template à rendre (ex: "Home", "User").
     * @param array $datas Les données à passer au template.
     * @return void
     * @throws \Exception Si le template n'existe pas ou ne peut pas être rendu.
     */
    public function render(string $templateName, array $datas = []): void
    {
        try {
            $this->validateTemplateName($templateName);
            $html = $this->renderTemplate($templateName, $datas);
            $this->outputHtml($html);
        } catch (\Exception $e) {
            $this->handleRenderError($e->getMessage());
        }
    }

    /**
     * Valide le nom du template.
     *
     * @param string $templateName Le nom du template à valider.
     * @return void
     * @throws \Exception Si le nom du template est invalide.
     */
    private function validateTemplateName(string $templateName): void
    {
        if (empty($templateName) || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $templateName)) {
            throw new \Exception("Invalid template name: " . $templateName);
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
    private function renderTemplate(string $templateName, array $datas): string
    {
        $className = 'Templates\\' . ucfirst($templateName) . 'Template';
        $methodName = 'display' . ucfirst($templateName);

        if (!class_exists($className)) {
            throw new \Exception("Template class not found: " . $className);
        }

        if (!method_exists($className, $methodName)) {
            throw new \Exception("Template method not found: " . $methodName);
        }

        if (!is_callable([$className, $methodName])) {
            throw new \Exception("Template method not callable: " . $methodName);
        }

        $result = $className::$methodName($datas);
        
        if (!is_string($result)) {
            throw new \Exception("Template method must return a string");
        }

        return $result;
    }

    /**
     * Affiche le HTML rendu.
     *
     * @param string $html Le HTML à afficher.
     * @return void
     */
    private function outputHtml(string $html): void
    {
        echo $html;
        exit();
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