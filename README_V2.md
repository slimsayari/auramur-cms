> La documentation suivante concerne les extensions apportées au projet initial.
> Pour la documentation de base, veuillez vous référer au `README.md`.

# Auramur Headless CMS v2 - Extensions & Fonctionnalités Avancées

Ce document détaille les nouvelles fonctionnalités ajoutées au CMS Auramur pour le transformer en un backoffice opérationnel complet.

**Lead Developer:** Manus AI
**Date:** 23/12/2025

## 1. Objectifs de l'Extension

L'objectif de cette mise à jour est de doter le CMS d'outils métier indispensables à une exploitation en production :

-   **Interface d'Administration** : Une interface complète pour gérer le contenu sans avoir à utiliser l'API directement.
-   **Gestion SEO Avancée** : Contrôle total sur le référencement des produits, articles et catégories.
-   **Produits Variables** : Support des variantes de produits (dimensions, prix, SKU) pour un catalogue e-commerce réaliste.
-   **Import de Données** : Un mécanisme d'import one-shot pour migrer les données depuis WooCommerce.
-   **Synchronisation Externe** : Un service d'export pour alimenter un moteur de recherche externe comme Typesense.

## 2. Nouvelles Fonctionnalités

### 2.1 Interface d'Administration (EasyAdmin)

Une interface d'administration a été mise en place avec **EasyAdminBundle**, offrant une gestion visuelle et intuitive des entités.

**Accès** : `https://127.0.0.1:8000/admin`

**Fonctionnalités** :
-   **Dashboard** : Vue d'ensemble et navigation.
-   **CRUD Produits** : Gestion complète des produits, avec édition inline des variantes et du SEO.
-   **CRUD Articles** : Gestion des articles de blog.
-   **CRUD Catégories & Tags** : Organisation du contenu.
-   **Validation IA** : Interface pour valider ou rejeter les contenus générés par IA.
-   **Outils d'Import/Export** : Liens vers les actions d'import WooCommerce et d'export Typesense.

### 2.2 Gestion SEO Avancée

Le SEO est maintenant géré via des entités dédiées (`ProductSeo`, `ArticleSeo`, `CategorySeo`) pour une meilleure séparation des préoccupations.

**Champs SEO disponibles** :
-   `seoTitle` : Titre pour les moteurs de recherche (max 60 caractères).
-   `metaDescription` : Description pour les SERPs (max 160 caractères).
-   `slug` : URL-friendly et unique.
-   `canonicalUrl` : URL canonique pour éviter le contenu dupliqué.
-   `noindex` / `nofollow` : Contrôle de l'indexation.
-   `schemaReady` : Active la génération de données structurées (JSON-LD) pour les produits.

### 2.3 Produits Variables

Le CMS supporte désormais les produits variables, essentiels pour un site e-commerce.

-   **Entité `ProductVariant`** : Chaque variante possède son propre SKU, nom, dimensions, prix au m², stock et statut.
-   **Relation `Product` -> `ProductVariant`** : Un produit peut avoir plusieurs variantes.
-   **Gestion Admin** : Les variantes peuvent être ajoutées, modifiées et supprimées directement depuis la page d'édition d'un produit dans EasyAdmin.

### 2.4 Import WooCommerce

Un service d'import one-shot a été créé pour faciliter la migration depuis un site WooCommerce existant.

-   **Service `WooProductImporter`** : Logique d'import robuste et transactionnelle.
-   **Formats supportés** : JSON ou CSV.
-   **Endpoint** : `POST /api/admin/import/woocommerce` (protégé).
-   **Mapping complet** : Importe les produits, variantes, images, catégories, tags et métadonnées SEO.
-   **Statut par défaut** : Tous les produits importés sont créés avec le statut `DRAFT` pour validation manuelle.
-   **Logs d'Import** : L'entité `WooImportLog` trace chaque opération d'import pour l'audit et le débogage.

### 2.5 Export Typesense

Pour découpler la recherche du CMS, un service d'export vers Typesense a été mis en place.

-   **Service `TypesenseExporter`** : Gère la communication avec l'API de Typesense.
-   **Payload Normalisé** : Un DTO (`TypesenseProductPayloadDTO`) assure un format de données cohérent pour l'indexation.
-   **Déclenchement** :
    -   **Automatique** : À la publication ou dépublication d'un produit.
    -   **Manuel** : Via l'endpoint `POST /api/admin/export/typesense`.
-   **Dépendance Faible** : Le CMS n'a pas de dépendance forte à Typesense. Si l'export échoue, une erreur est loggée mais l'application continue de fonctionner.

## 3. Architecture et Services Clés

L'architecture existante a été étendue avec de nouveaux services dédiés :

-   `SeoService` : Gère la création, la mise à jour et la validation des métadonnées SEO.
-   `VariantService` : Encapsule la logique de gestion des variantes de produits.
-   `PublicationService` : Orchestre le workflow de publication, incluant les vérifications pré-publication et le déclenchement de l'export Typesense.
-   `WooProductImporter` : Gère l'import de données.
-   `TypesenseExporter` : Gère l'export de données.

## 4. Instructions de Mise à Jour

1.  **Installer les nouvelles dépendances** :

    ```bash
    composer require easycorp/easyadmin-bundle
    composer require symfony/messenger
    composer require symfony/http-client
    ```

2.  **Configurer les variables d'environnement** :

    Ajoutez les lignes suivantes à votre fichier `.env.local` et configurez-les :

    ```env
    # .env.local
    TYPESENSE_HOST=http://typesense:8108
    TYPESENSE_API_KEY=your-typesense-api-key
    ```

3.  **Appliquer les nouvelles migrations** :

    ```bash
    php bin/console doctrine:migrations:migrate
    ```

4.  **Vider le cache** (recommandé) :

    ```bash
    php bin/console cache:clear
    ```

5.  **Accéder à l'interface d'administration** :

    Rendez-vous sur `https://127.0.0.1:8000/admin` et connectez-vous avec vos identifiants administrateur.
