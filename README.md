# GSB Central - Système de Gestion pour Galaxy-Swiss Bourdin

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)

## 📋 Présentation du Projet

**GSB Central** est une application web de gestion développée pour **Galaxy-Swiss Bourdin**, une entreprise pharmaceutique fictive. Ce projet a été réalisé dans le cadre du **BTS SIO** (Services Informatiques aux Organisations).

L'application permet de gérer les commandes, le stock et les relations avec les entreprises clientes de manière centralisée et sécurisée.

🌐 **Version en ligne** : [https://gsb-nolan-liogier.fr/](https://gsb-nolan-liogier.fr/)

---

## 🎯 Objectifs du Projet

Cette application répond aux besoins quotidiens d'une entreprise pharmaceutique en proposant :

- **Gestion des commandes** : création, modification, validation et suivi des commandes clients
- **Gestion du stock** : suivi des produits disponibles et de leurs quantités
- **Gestion des entreprises** : administration des clients et de leurs informations
- **Tableau de bord personnalisé** : vue d'ensemble adaptée selon le rôle de l'utilisateur

---

## 👥 Rôles et Permissions

L'application distingue trois types d'utilisateurs, chacun ayant des droits et des accès spécifiques :

### 🧑‍💼 Commercial

Le commercial est l'interface entre l'entreprise et ses clients. Il peut :

- **Consulter et gérer les entreprises** qui lui sont assignées
- **Créer et modifier des commandes** pour ses clients
- **Valider les commandes** en attente avant leur envoi
- **Supprimer des commandes** qui n'ont pas encore été envoyées
- **Visualiser un tableau de bord** avec les statistiques de ses ventes

**Accès autorisés** : Entreprises, Commandes, Tableau de bord

---

### 🏢 Client

Le client représente une entreprise qui passe des commandes. Il peut :

- **Consulter les informations de sa propre entreprise**
- **Créer des commandes** pour ses besoins
- **Modifier ou annuler ses commandes** uniquement si elles sont encore en attente
- **Suivre l'état de ses commandes** (en attente, validée, envoyée)
- **Visualiser un tableau de bord** avec ses statistiques personnelles

**Accès autorisés** : Sa propre entreprise, Ses commandes, Tableau de bord

**Limitations** : Un client ne peut pas accéder aux informations d'autres entreprises ni modifier des commandes déjà validées.

---

### 📦 Logisticien

Le logisticien gère l'approvisionnement et la logistique. Il peut :

- **Consulter l'ensemble des commandes** pour organiser les préparations
- **Gérer le stock** : ajouter, modifier ou supprimer des produits
- **Visualiser les quantités disponibles** pour chaque produit
- **Suivre les commandes** pour planifier les expéditions
- **Visualiser un tableau de bord** avec les statistiques du stock et des commandes

**Accès autorisés** : Commandes, Stock, Tableau de bord

**Limitations** : Le logisticien ne peut pas modifier ou valider les commandes, seulement les consulter pour organiser le travail logistique.

---

## 🔐 Sécurité et Authentification

L'application garantit la sécurité des données grâce à :

- **Authentification obligatoire** : chaque utilisateur doit se connecter avec ses identifiants
- **Gestion des permissions** : chaque rôle a accès uniquement aux fonctionnalités qui lui sont autorisées
- **Protection des données** : un client ne peut consulter que ses propres informations
- **Sessions sécurisées** : protection contre les attaques et les accès non autorisés

---

## 💡 Fonctionnalités Principales

### Tableau de Bord

Chaque utilisateur accède à un tableau de bord personnalisé affichant les statistiques pertinentes pour son rôle :

- **Commercial** : nombre de commandes, chiffre d'affaires, entreprises gérées
- **Client** : état de ses commandes, historique, montants
- **Logisticien** : état du stock, commandes à préparer, alertes de réapprovisionnement

### Gestion des Commandes

- Création de commandes avec sélection de produits
- Modification des commandes selon leur statut
- Validation des commandes par les commerciaux
- Suivi de l'état des commandes (en attente, validée, envoyée)
- Génération de documents PDF

### Gestion du Stock

- Consultation de l'inventaire complet
- Ajout de nouveaux produits
- Modification des quantités et informations produits
- Suivi des stocks disponibles

### Gestion des Entreprises

- Consultation des informations clients
- Modification des données d'entreprise
- Association entre commerciaux et entreprises

---

## 🛠️ Technologies Utilisées

L'application a été développée avec des technologies web modernes :

- **PHP** : langage de programmation pour le backend
- **MariaDB** : base de données pour le stockage des informations
- **Tailwind CSS** : framework CSS pour l'interface utilisateur moderne et responsive

---

## 👨‍💻 Auteur

**Nolan Liogier**

- GitHub : [https://github.com/nolanliogier](https://github.com/nolanliogier)
- Site web : [https://gsb-nolan-liogier.fr/](https://gsb-nolan-liogier.fr/)

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 📝 Contexte Pédagogique

Ce projet a été développé dans le cadre de la formation **BTS SIO** (Services Informatiques aux Organisations), option **SLAM** (Solutions Logicielles et Applications Métier). Il démontre la maîtrise de :

- La conception et le développement d'applications web
- La gestion des bases de données
- La sécurité des applications
- La gestion des utilisateurs et des permissions
- L'architecture logicielle (MVC)

---

*Dernière mise à jour : 2024*
