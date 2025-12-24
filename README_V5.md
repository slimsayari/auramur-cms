# Module E-commerce (v5) - Documentation

Cette version introduit un module e-commerce léger et découplé, axé sur la gestion des clients, des commandes et la confirmation de paiement via des webhooks externes. Le CMS ne gère ni le panier d'achat ni le processus de paiement direct, se concentrant sur la persistance des données post-paiement.

## 1. Architecture et Principes

Le module e-commerce respecte l'architecture DDD-light du projet, en séparant clairement les préoccupations :

- **Domain** : Contient les entités `Customer`, `Order`, `OrderItem` et les énumérations `OrderStatus` et `PaymentProvider`.
- **Application** : Héberge les services métier comme `OrderService` pour la logique de création et de mise à jour des commandes.
- **Infrastructure** : Comprend les contrôleurs API, les webhooks, la configuration de la sécurité (JWT, Voters) et les implémentations concrètes (ex: `WebhookPaymentHandler`).
- **Presentation** : Concerne principalement l'interface d'administration EasyAdmin pour la visualisation des clients et des commandes.

### Principes clés :

- **CMS Headless** : Le front-end (non inclus dans ce projet) est responsable du tunnel d'achat, du panier et de l'intégration avec les SDK de paiement (Stripe.js, PayPal SDK).
- **Pas de panier côté serveur** : Pour rester stateless et léger, le CMS ne gère pas de panier. L'état du panier est entièrement géré côté client.
- **Webhooks pour la persistance** : La création des commandes dans le CMS est déclenchée exclusivement par des webhooks provenant des prestataires de paiement (Stripe, PayPal) après un paiement réussi.
- **Sécurité** : L'accès aux données client est sécurisé par JWT. Les clients ne peuvent accéder qu'à leurs propres commandes grâce à un `OrderVoter`.

## 2. Entités

### `Customer`

Représente un client. Implémente `UserInterface` et `PasswordAuthenticatedUserInterface` de Symfony pour l'authentification.

- `email` : Identifiant unique.
- `password` : Hashé via les mécanismes de Symfony.
- `firstName`, `lastName` : Informations de profil.
- `orders` : Relation `OneToMany` vers les commandes du client.
- `isActive`, `lastLoginAt`, `createdAt`, `updatedAt`, `deletedAt`.

### `Order`

Représente une commande passée par un client.

- `reference` : Référence unique de la commande (générée par le `OrderService`).
- `customer` : Relation `ManyToOne` vers le client propriétaire.
- `status` : Statut de la commande (enum `OrderStatus` : `PAID`, `PROCESSING`, `COMPLETED`, `CANCELLED`).
- `totalAmount`, `currency` : Montant total et devise.
- `paymentProvider`, `paymentReference` : Informations sur le paiement.
- `items` : Relation `OneToMany` vers les `OrderItem`.

### `OrderItem`

Détail d'un article dans une commande.

- `order` : Relation `ManyToOne` vers la commande parente.
- `productId`, `productName`, `variantId`, `variantName` : Données dénormalisées du produit/variante au moment de l'achat pour l'archivage historique.
- `quantity`, `unitPrice` : Quantité et prix unitaire.

## 3. Authentification Client (JWT)

L'authentification des clients est gérée par `LexikJWTAuthenticationBundle`.

- **Firewall** : Un firewall `customer` est configuré dans `security.yaml` pour les routes `/api/customer` et `/api/orders`.
- **Endpoints d'authentification** :
    - `POST /api/customer/register` : Inscription d'un nouveau client (`CustomerAuthController`).
    - `POST /api/login_check` : Connexion et obtention d'un token JWT.
- **Gestion de profil** :
    - `GET /api/customer/profile` : Récupérer le profil du client authentifié.
    - `PUT /api/customer/profile` : Mettre à jour le profil du client.

## 4. API & Webhooks

### Endpoints Commandes

Protégés par JWT et le `OrderVoter`.

- `GET /api/orders` : Liste les commandes du client authentifié.
- `GET /api/orders/{id}` : Affiche les détails d'une commande spécifique appartenant au client.

### Endpoints Webhooks

Ces endpoints sont publics mais sécurisés par la vérification de la signature de la requête.

- `POST /api/webhooks/stripe` : Gère les événements de paiement Stripe (ex: `checkout.session.completed`).
- `POST /api/webhooks/paypal` : Gère les événements de paiement PayPal.

Le `WebhookPaymentHandler` est le service central qui :
1.  Vérifie la signature de la requête (avec la clé secrète du webhook).
2.  Parse la charge utile (payload) pour extraire les informations de paiement.
3.  Appelle le `OrderService` pour créer la commande en base de données.

## 5. Configuration

### Variables d'environnement

Ajoutez les clés secrètes de vos webhooks dans le fichier `.env.local` :

```dotenv
STRIPE_WEBHOOK_SECRET="whsec_..."
PAYPAL_WEBHOOK_SECRET="..."
```

Ces variables sont utilisées par le `WebhookPaymentHandler` pour valider les requêtes entrantes.

## 6. Interface d'Administration (EasyAdmin)

Le module e-commerce est intégré à l'interface d'administration pour permettre aux administrateurs de visualiser les données.

- **Clients** : Une vue CRUD complète pour gérer les clients est disponible dans `E-commerce > Clients`.
- **Commandes** : Une vue en **lecture seule** des commandes est disponible dans `E-commerce > Commandes`. La création et la suppression sont désactivées car ces actions sont pilotées par les webhooks et la logique métier.

Le détail d'une commande affiche la liste des articles commandés dans un tableau formaté.
