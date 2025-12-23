# Auramur Headless CMS - Architecture & Documentation

Ce document détaille l'architecture et la mise en place du CMS headless pour Auramur, développé avec Symfony 6.4 et API Platform.

**Lead Developer:** Manus AI (agissant en tant que Lead Developer Symfony avec 10+ ans d'expérience)
**Date:** 23/12/2025

## 1. Contexte et Objectifs

Le projet vise à créer un mini CMS headless pour un site e-commerce de papier peint. Ce CMS est conçu pour être léger et se concentre sur les fonctionnalités essentielles :

- **Gestion de contenu** : CRUD pour les produits et les articles.
- **Validation Humaine** : Workflow de validation pour les contenus générés par une IA externe (via n8n).
- **API Publique** : Exposition d'une API REST propre et performante pour le site frontend.
- **Orchestration IA** : Intégration avec des workflows n8n pour l'import de contenu.

La stack technique imposée est respectée, avec une architecture propre inspirée des principes DDD-light.

## 2. Stack Technique

| Composant | Version/Technologie | Rôle |
|---|---|---|
| **Framework** | Symfony 6.4 LTS | Cœur de l'application, gestion des services, sécurité. |
| **API** | API Platform 3.2 | Création rapide d'une API REST/GraphQL hypermedia. |
| **ORM** | Doctrine ORM | Mapping objet-relationnel et gestion de la base de données. |
| **Base de données** | PostgreSQL (production) / SQLite (développement) | Persistance des données. |
| **Sécurité** | Symfony Security | Authentification simple pour l'interface d'administration. |
| **Dépendances clés** | `ramsey/uuid-doctrine` | Gestion des identifiants UUID. |

## 3. Architecture Globale

L'application suit une architecture en couches pour séparer clairement les responsabilités :

1.  **Couche de Présentation (Controllers & API Resources)** : Gère les requêtes HTTP. Les `Controllers` sont utilisés pour les actions spécifiques (webhooks, validation) tandis qu'`API Platform` gère les opérations CRUD standard.
2.  **Couche Application (Services & DTOs)** : Contient la logique métier. Les `Services` orchestrent les opérations et les `DTOs` (Data Transfer Objects) valident et transportent les données entre les couches.
3.  **Couche Domaine (Entities & Enums)** : Représente le cœur du métier avec les `Entities` Doctrine et les `Enums` pour les statuts et types.
4.  **Couche Infrastructure (Repositories & Doctrine)** : Gère la persistance des données et les requêtes complexes vers la base de données.

```mermaid
graph TD
    subgraph Frontend
        A[Site E-commerce React/Vue]
    end

    subgraph "Auramur CMS (Symfony)"
        subgraph "Couche Présentation"
            B[API Platform Resources] -- CRUD public --> A
            C[Admin Controllers] -- Actions sécurisées --> D{Admin UI}
            E[Webhook Controller] -- Import n8n --> F[n8n]
        end

        subgraph "Couche Application"
            G[Services<br>(ProductService, ValidationService...)]
            H[DTOs<br>(ProductCreateDTO...)]
        end

        subgraph "Couche Domaine"
            I[Entities<br>(Product, Article...)]
            J[Enums<br>(ContentStatus...)]
        end

        subgraph "Couche Infrastructure"
            K[Doctrine Repositories]
            L[PostgreSQL / SQLite]
        end
    end

    A -- "GET /api/products" --> B
    C -- Utilise --> G
    E -- Utilise --> G
    B -- Appelle --> G
    G -- Valide avec --> H
    G -- Manipule --> I
    G -- Utilise --> K
    K -- Interagit avec --> L
    I -- Mappé sur --> L
```

## 4. Structure des Dossiers

La structure du projet est organisée par type de composant, favorisant la clarté et la maintenabilité.

```
auramur-cms/
├── src/
│   ├── ApiResource/    # Ressources exposées par API Platform
│   ├── Controller/
│   │   └── Admin/      # Contrôleurs pour l'API d'administration
│   ├── DTO/            # Data Transfer Objects pour la validation
│   ├── Entity/         # Entités Doctrine
│   ├── Enum/           # Énumérations PHP (statuts, types)
│   ├── Exception/      # Exceptions métier personnalisées
│   ├── Repository/     # Repositories Doctrine
│   └── Service/        # Services contenant la logique métier
├── config/             # Fichiers de configuration
├── migrations/         # Migrations de base de données
├── .env                # Variables d'environnement (template)
└── .env.local          # Variables d'environnement locales
```

## 5. Endpoints de l'API

L'API est divisée en trois sections : publique, administration et webhooks.

### API Publique (Lecture seule)

Ces endpoints sont ouverts et ne retournent que le contenu avec le statut `PUBLISHED`.

| Méthode | Endpoint | Description |
|---|---|---|
| `GET` | `/api/products` | Liste les produits publiés (filtrable, paginé). |
| `GET` | `/api/products/{id}` | Récupère un produit publié par son UUID. |
| `GET` | `/api/articles` | Liste les articles publiés. |
| `GET` | `/api/articles/{id}` | Récupère un article publié. |
| `GET` | `/api/categories` | Liste toutes les catégories. |
| `GET` | `/api/tags` | Liste tous les tags. |

### API d'Administration (Protégée)

Ces endpoints nécessitent une authentification `ROLE_ADMIN`.

| Méthode | Endpoint | Description |
|---|---|---|
| `POST` | `/api/admin/products` | Crée un nouveau produit. |
| `PATCH` | `/api/admin/products/{id}` | Met à jour un produit. |
| `DELETE` | `/api/admin/products/{id}` | Supprime un produit. |
| `PATCH` | `/api/admin/products/{id}/publish` | Passe le statut d'un produit à `PUBLISHED`. |
| `POST` | `/api/admin/articles` | Crée un nouvel article. |
| `PATCH` | `/api/admin/articles/{id}` | Met à jour un article. |
| `GET` | `/api/admin/ai-generations` | Liste les générations IA. |
| `PATCH` | `/api/admin/ai-validations/{id}/validate` | Valide une génération IA et applique le contenu. |
| `PATCH` | `/api/admin/ai-validations/{id}/reject` | Rejette une génération IA. |

### Webhooks

| Méthode | Endpoint | Description |
|---|---|---|
| `POST` | `/api/webhooks/ai-generations` | Endpoint pour recevoir le contenu généré par n8n. Protégé par un token secret. |

## 6. Workflow de Validation de Contenu IA

Le processus de validation est central au CMS.

1.  **Génération** : Un service externe (n8n) génère du contenu (ex: une description de produit).
2.  **Import** : n8n appelle le webhook `POST /api/webhooks/ai-generations` avec le contenu.
3.  **Stockage** : Le `AiGenerationService` crée une entité `AiGeneration` avec le statut `DRAFT` et l'associe au produit ou à l'article concerné.
4.  **Validation** : Un administrateur, via une interface, consulte les générations en attente (`GET /api/admin/ai-validations/pending`).
5.  **Action** :
    *   **Validation** : L'admin appelle `PATCH /api/admin/ai-validations/{id}/validate`. Le `ValidationService` met à jour le statut de la génération à `VALIDATED` et applique le contenu à l'entité parente (ex: met à jour la description du produit).
    *   **Rejet** : L'admin appelle `PATCH /api/admin/ai-validations/{id}/reject`. Le statut passe à `ARCHIVED` avec un motif de rejet.
6.  **Publication** : Une fois le contenu validé et affiné, l'administrateur publie le produit ou l'article (`PATCH /api/admin/products/{id}/publish`).

## 7. Instructions de Mise en Place

1.  **Cloner le projet** :
    ```bash
    git clone <votre-repo>
    cd auramur-cms
    ```

2.  **Installer les dépendances** :
    ```bash
    composer install
    ```

3.  **Configurer l'environnement** :
    Copiez `.env` vers `.env.local` et configurez vos variables, notamment `DATABASE_URL` et `WEBHOOK_SECRET`.
    ```bash
    cp .env .env.local
    # Éditez .env.local
    ```
    Pour le développement, la base de données SQLite est préconfigurée.

4.  **Créer la base de données et les migrations** :
    ```bash
    php bin/console doctrine:database:create --if-not-exists
    php bin/console doctrine:migrations:migrate
    ```

5.  **Lancer le serveur** :
    Utilisez le binaire Symfony CLI pour lancer le serveur de développement.
    ```bash
    symfony server:start
    ```

L'API sera accessible à l'adresse `https://127.0.0.1:8000/api`.

## 8. Choix Techniques et Bonnes Pratiques

- **UUID v7** : Utilisé comme clé primaire pour toutes les entités pour des raisons de performance et de non-séquentialité.
- **DTOs pour l'API** : Le pattern DTO est utilisé pour découpler les objets de l'API des entités Doctrine. Cela permet une validation fine et une transformation des données entrantes avant de toucher au modèle de données.
- **Services Métier** : Toute la logique est encapsulée dans des services, rendant les contrôleurs légers et la logique réutilisable et testable.
- **Enums PHP 8.1** : Les statuts (`ContentStatus`) et types (`AiGenerationType`) sont gérés via des Enums pour un code plus sûr et plus lisible.
- **Sécurité** : L'accès à l'API d'administration est restreint via `access_control` et `denyAccessUnlessGranted`. Le webhook est protégé par un simple token partagé.
- **Configuration par environnement** : Le projet utilise les fichiers `.env` de Symfony pour gérer la configuration de manière flexible entre les environnements de `dev`, `test` et `prod`.
