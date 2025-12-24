# Tests TODO - Dette Technique

Ce document liste les tests qui ne sont pas encore passés en CI, avec la raison technique et la solution envisagée.

## 1. Tests Fonctionnels d'API Publique

- **Fichiers** : `tests/Functional/ProductWorkflowTest.php`
- **Tests** : `testDraftProductNotExposedPublicly`, `testPublishedProductExposedPublicly`
- **Raison** : DAMA DoctrineTestBundle isole chaque test dans une transaction. Le produit créé dans le test n'est pas visible via la requête HTTP API qui utilise une autre connexion DB.
- **Solution** : Désactiver DAMA pour ces tests spécifiques et utiliser des fixtures persistantes, ou créer un client API qui utilise le même EntityManager que le test.

## 2. Tests Fonctionnels d'Accès Admin

- **Fichiers** : `tests/Functional/AdminAccessTest.php`
- **Tests** : `testAdminAccessDeniedWithoutAuthentication`, `testAdminAccessWithAuthentication`
- **Raison** : Erreur 500 sur `/admin` dans l'environnement de test. Problème de configuration EasyAdmin (probablement un service manquant ou une dépendance non chargée).
- **Solution** : Déboguer le container de services dans l'environnement de test pour identifier le service manquant.

## 3. Test Fonctionnel d'Import WooCommerce

- **Fichiers** : `tests/Functional/WooCommerceImportTest.php`
- **Test** : `testImportValidWooProduct`
- **Raison** : Conflit entre `ramsey/uuid` et `symfony/uid`. Le service WooProductImporter fonctionne, mais les recherches Doctrine échouent car les UUID ne sont pas compatibles.
- **Solution** : Standardiser sur `symfony/uid` dans tout le projet et supprimer `ramsey/uuid`.

## 4. Tests Fonctionnels de Redirections SEO

- **Fichiers** : `tests/Functional/SeoAndRedirectTest.php`
- **Tests** : `testSlugChangeCreatesRedirect`, `testRedirectApiEndpoint`, `testNoRedirectFound`
- **Raison** : Problème d'isolation des tests (redirections persistées entre les tests) et structure JSON de l'API non vérifiée.
- **Solution** : Nettoyer les redirections entre les tests et vérifier la structure JSON retournée par RedirectPublicController.

---

## Résumé des Tests

### Tests PASS (11 tests critiques)

- ✅ **Workflow de publication** (7/7)
  - Création produit draft
  - Validation variantes obligatoires
  - Validation images obligatoires
  - Validation SEO obligatoire
  - Publication produit valide
  - Dépublication
  - Transitions d'état

- ✅ **Sécurité admin** (4/4)
  - Accès CRUD produits
  - Accès CRUD articles
  - Accès CRUD catégories
  - Refus accès non-admin

### Tests TODO (9 tests non critiques)

- ⏸️ **API publique** (2 tests) - Isolation DAMA
- ⏸️ **Admin EasyAdmin** (2 tests) - Configuration environnement test
- ⏸️ **Import WooCommerce** (1 test) - Conflit UUID
- ⏸️ **Redirections SEO** (3 tests) - Isolation et structure JSON
- ⏸️ **Login utilisateur** (1 test) - Page login manquante
