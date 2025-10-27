<?php

namespace App\Helpers;

use Routing\Router;
use Templates\BaseTemplate;

/**
 * Classe RenderService
 * 
 * Service centralisé de rendu des templates avec sécurité et validation.
 * Gère le chargement dynamique des templates, la validation des noms de templates,
 * et la gestion des erreurs de rendu pour éviter les fuites d'information.
 */
class RenderService
{
    /**
     * Template de base qui encapsule tous les autres templates.
     * Fournit le layout principal (header, footer, navigation, etc.).
     * 
     * @var BaseTemplate
     */
    private BaseTemplate $baseTemplate;

    /**
     * Initialise le service de rendu en créant le template de base.
     * Le template de base sera réutilisé pour toutes les pages.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->baseTemplate = new BaseTemplate();
    }
    
    /**
     * Rend et affiche un template spécifique avec les données fournies.
     * 
     * Valide le nom du template pour éviter les injections, charge le template approprié,
     * l'encapsule dans le template de base, puis affiche le résultat. Gère les erreurs
     * de chargement ou d'exécution avec une page d'erreur dédiée.
     *
     * @param string $templateName Le nom du template à rendre (ex: "Home", "User"). Doit respecter le pattern [a-zA-Z][a-zA-Z0-9]*
     * @param array $datas Les données à passer au template.
     * @param string $templateTitle Le titre de la page à afficher dans le template de base.
     * @return void
     * @throws \Exception Si le template n'existe pas ou ne peut pas être rendu.
     */
    public function displayTemplates(string $templateName, array $datas = [], string $templateTitle = ''): void
    {
        try {
            // Validation stricte du nom de template pour prévenir les injections de classe (LFI/RCE)
            // Pattern: commence par une lettre, suivi de lettres et chiffres uniquement
            if (empty($templateName) || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $templateName)) {
                throw new \Exception("Invalid template name: " . $templateName);
            }

            // Récupération du contenu du template via chargement dynamique sécurisé
            $content = $this->getTemplateContent($templateName, $datas);
            
            // Rendu final : encapsulation dans le template de base avec titre et route
            echo $this->baseTemplate->render( $templateTitle,  $content,  '/' . $templateName);
            exit();

        } catch (\Exception $e) {
            // Gestion des erreurs sans exposer les détails techniques à l'utilisateur final
            $this->handleRenderError($e->getMessage());
        }
    }

    /**
     * Charge dynamiquement et exécute le template spécifié avec les données fournies.
     * 
     * Construit le nom de classe et de méthode selon les conventions de nommage
     * (ex: "Home" -> Templates\HomeTemplate::displayHome). Vérifie l'existence
     * de la classe et de la méthode, puis exécute le template de manière sécurisée.
     * Valide que le résultat est bien une chaîne de caractères.
     *
     * @param string $templateName Le nom du template à rendre.
     * @param array $datas Les données à passer au template.
     * @return string Le HTML rendu par le template.
     * @throws \Exception Si le template ou sa méthode n'existe pas, ou si le résultat n'est pas une string.
     */
    private function getTemplateContent(string $templateName, array $datas): string
    {
        // Construction des noms de classe et méthode selon la convention de nommage
        // Ex: "Home" devient "Templates\HomeTemplate::displayHome()"
        $className = 'Templates\\' . ucfirst($templateName) . 'Template';
        $methodName = 'display' . ucfirst($templateName);

        // Vérification de l'existence de la classe avant l'instanciation
        if (!class_exists($className)) {
            throw new \Exception("Template class not found: " . $className);
        }

        // Vérification de l'existence de la méthode avant l'appel
        if (!method_exists($className, $methodName)) {
            throw new \Exception("Template method not found: " . $methodName);
        }

        // Instanciation du template et vérification de la callabilité de la méthode
        $templateInstance = new $className();
        if (!is_callable([$templateInstance, $methodName])) {
            throw new \Exception("Template method not callable: " . $methodName);
        }

        // Exécution du template et récupération du résultat
        $result = $templateInstance->$methodName($datas);
        
        // Validation du type de retour : le template doit retourner une chaîne HTML
        if (!is_string($result)) {
            throw new \Exception("Template method must return a string");
        }

        return $result;
    }

    /**
     * Gère et affiche les erreurs de rendu de manière sécurisée.
     * 
     * Affiche une page d'erreur générique lorsque le rendu d'un template échoue.
     * Échappe le message d'erreur avec htmlspecialchars pour éviter les injections XSS.
     * Affiche une erreur directe plutôt qu'une redirection pour éviter les boucles infinies
     * si le template de base lui-même est défaillant.
     *
     * @param string $errorMessage Le message d'erreur à afficher (sera échappé).
     * @return void
     */
    private function handleRenderError(string $errorMessage): void
    {
        // Affichage direct d'une erreur au lieu de rediriger pour éviter les boucles infinies
        // Cela se produit si même le template de base ou la page d'erreur échoue
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