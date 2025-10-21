<?php

namespace Templates;

/**
 * Classe HomeTemplate
 * Gère l\'affichage du template de la page d\'accueil.
 */
class HomeTemplate {
    /**
     * Affiche le contenu HTML de la page d\'accueil.
     *
     * @param array $datas Données à utiliser pour le template.
     * @return void
     */
    public static function displayHome($datas) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($datas['message']); ?></h1>
</body>
</html>
<?php
    }
}