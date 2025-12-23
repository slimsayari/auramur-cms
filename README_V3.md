> La documentation suivante concerne les fonctionnalités structurelles ajoutées au projet v2.
> Pour la documentation des versions précédentes, veuillez vous référer à `README.md` et `README_V2.md`.

# Auramur Headless CMS v3 - Fonctionnalités Structurelles & Production

Ce document détaille les fonctionnalités structurelles ajoutées au CMS Auramur pour le sécuriser et le préparer à un usage en production. L'objectif est de **compléter et solidifier l'existant** sans refondre le projet.

**Lead Developer:** Manus AI
**Date:** 23/12/2025

## 1. Objectifs de la Mise à Jour

Cette version introduit des fonctionnalités non-fonctionnelles mais critiques pour la robustesse, la sécurité et la maintenabilité à long terme du CMS :

-   **Workflow de Publication Robuste** : Un cycle de vie de contenu clair et contrôlé.
-   **Versioning de Contenu** : Un historique des modifications pour l'audit et le rollback.
-   **Soft Delete & Archivage** : Pour ne jamais perdre de données critiques.
-   **Gestion des Redirections SEO** : Pour maintenir le référencement lors des changements d'URL.
-   **Gestion des Slugs Unifiée** : Pour éviter les collisions d'URL.
-   **Mode Preview Sécurisé** : Pour visualiser les brouillons en contexte.
-   **Support Multi-Langue (Prêt)** : Une architecture prête pour l'internationalisation.
-   **Webhooks Sortants Fiables** : Pour notifier les systèmes externes de manière asynchrone.
-   **Traçabilité IA Complète** : Pour un audit complet des générations de contenu.

## 2. Nouvelles Fonctionnalités Structurelles

### 2.1 Workflow de Publication

Un workflow d'état a été mis en place pour les `Product` et `Article`.

-   **États** : `DRAFT` → `READY_FOR_REVIEW` → `VALIDATED` → `PUBLISHED` ↔ `ARCHIVED`.
-   **Service `PublicationWorkflowService`** : Orchestre toutes les transitions d'état, garantissant qu'aucune règle métier n'est violée.
-   **Endpoints** : Des endpoints dédiés (`/api/admin/products/{id}/workflow/*`) permettent de déclencher les transitions.

### 2.2 Versioning de Contenu (Light)

Un système de versioning simple a été ajouté pour tracer les modifications.

-   **Entité `ContentVersion`** : Stocke un snapshot JSON des champs importants à chaque modification.
-   **Déclenchement Automatique** : Une nouvelle version est créée à chaque changement de statut ou mise à jour majeure.
-   **Rollback Manuel** : L'endpoint `POST /api/admin/products/{id}/versions/{versionNumber}/rollback` permet de restaurer une version précédente.

### 2.3 Soft Delete & Archivage

-   **Aucun Hard Delete** : Les produits et articles ne sont jamais supprimés physiquement de la base de données.
-   **Champs `deletedAt` et `archivedAt`** : Utilisés pour marquer les contenus comme supprimés ou archivés.
-   **Filtres Doctrine** : Les requêtes publiques excluent automatiquement les contenus qui ont `deletedAt` ou `archivedAt` de défini.

### 2.4 Redirections SEO

Un gestionnaire de redirections a été créé pour préserver le SEO.

-   **Entité `Redirect`** : Stocke les redirections (`sourcePath`, `targetPath`, `type` de redirection).
-   **Création Automatique** : Une redirection 301 est créée automatiquement lors d'un changement de slug.
-   **Endpoint Public** : `GET /api/redirects/check?path=/ancienne-url` permet au frontend de vérifier si une redirection existe.

### 2.5 Gestion des Slugs Unifiée

-   **Entité `SlugRegistry`** : Assure l'unicité globale des slugs à travers toutes les entités (`Product`, `Article`, `Category`).
-   **Service `SlugService`** : Gère la création, la validation et la mise à jour des slugs, prévenant ainsi les collisions.

### 2.6 Mode Preview

-   **Entité `PreviewToken`** : Des tokens temporaires et sécurisés sont générés pour prévisualiser le contenu non publié.
-   **Endpoint `GET /api/preview/{token}`** : Retourne le contenu (brouillon ou en révision) si le token est valide.

### 2.7 Support Multi-Langue (Prêt)

-   **Entité `Translation`** : La structure de la base de données est prête à stocker des traductions pour n'importe quel champ de n'importe quelle entité.
-   **Service `TranslationService`** : Fournit des méthodes pour lire et écrire des traductions, sans impacter les performances actuelles.

### 2.8 Webhooks Sortants

-   **Entité `WebhookEvent`** : Chaque événement à notifier (ex: `product.published`) est stocké en base de données.
-   **Traitement Asynchrone** : Un service (`WebhookDispatcher`) et une commande console peuvent être utilisés pour envoyer les webhooks de manière asynchrone, garantissant que les actions utilisateur ne sont pas ralenties.

### 2.9 Traçabilité IA Renforcée

L'entité `AiGeneration` a été étendue pour inclure :
-   `source` : D'où vient la génération (n8n, utilisateur, import).
-   `prompt` : Le prompt exact utilisé.
-   `validatedBy` : L'utilisateur qui a validé le contenu.
-   `validatedAt` : La date de validation.

## 3. Instructions d'Installation

1.  **Appliquer les nouvelles migrations** :

    ```bash
    php bin/console doctrine:migrations:migrate
    ```

2.  **Vider le cache** :

    ```bash
    php bin/console cache:clear
    ```

3.  **Lancer le worker de webhooks** (recommandé en production) :

    ```bash
    php bin/console messenger:consume async -vv
    ```
