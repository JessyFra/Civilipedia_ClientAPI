# 📖 Civilipédia

> Encyclopédie collaborative des grandes civilisations du monde — projet scolaire réalisé en PHP / Symfony / Vanilla JS.

---

## Sommaire

- [Présentation](#présentation)
- [Architecture](#architecture)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Prérequis](#prérequis)
- [Installation](#installation)
  - [1. Cloner le dépôt](#1-cloner-le-dépôt)
  - [2. Base de données](#2-base-de-données)
  - [3. Configurer et lancer l'API](#3-configurer-et-lancer-lapi)
  - [4. Configurer et lancer le client](#4-configurer-et-lancer-le-client)
- [Comptes de test](#comptes-de-test)
- [Structure du projet](#structure-du-projet)
- [Documentation utilisateur](#documentation-utilisateur)

---

## Présentation

Civilipédia est une application web de type wiki permettant la création, la modification et la consultation collaborative d'articles sur les grandes civilisations (Mayas, Vikings, Mongols, Incas, Rome, Égypte, etc.).

Le projet suit une architecture **découplée** : un client PHP/HTML/CSS communique exclusivement avec une API REST Symfony via des requêtes HTTP.

---

## Architecture

```
Civilipedia_ClientAPI/
├── civilipedia-api/      → API REST (Symfony 7, PHP 8.4, MySQL)
├── civilipedia-client/   → Client web (PHP, HTML, CSS, Vanilla JS)
├── civilipedia.sql       → Script SQL de création et seed de la base
└── documentation/
    └── documentation_utilisateur.pdf
```

Le client ne contient **aucune logique métier** : il se contente d'appeler l'API et d'afficher les réponses. Les deux sous-projets sont servis indépendamment.

---

## Fonctionnalités

| Fonctionnalité | Non connecté | Connecté | Admin |
|---|:---:|:---:|:---:|
| Lire un article | ✔ | ✔ | ✔ |
| Rechercher un article (titre et contenu) | ✔ | ✔ | ✔ |
| Consulter l'historique des modifications | ✔ | ✔ | ✔ |
| Créer un compte | ✔ | — | — |
| Se connecter | ✔ | ✔ | ✔ |
| Créer un article (éditeur WYSIWYG) | — | ✔ | ✔ |
| Modifier un article (éditeur WYSIWYG) | — | ✔ | ✔ |
| Supprimer ses propres articles | — | ✔ | ✔ |
| Modifier son profil (avatar + mot de passe) | — | ✔ | ✔ |
| Supprimer n'importe quel article | — | — | ✔ |
| Bannir / débannir un utilisateur | — | — | ✔ |

---

## Technologies utilisées

**API (`civilipedia-api`)**
- PHP 8.4
- Symfony 7
- Doctrine ORM
- LexikJWT (authentification JWT RS256)
- MySQL 8

**Client (`civilipedia-client`)**
- PHP 8 (rendu serveur)
- Vanilla JavaScript (SPA légère, routing hash)
- Bootstrap 5 (responsive)
- Quill.js (éditeur WYSIWYG)
- Font Awesome 6

---

## Prérequis

- **PHP** ≥ 8.2
- **Composer**
- **MySQL** (via XAMPP ou autre)
- **OpenSSL** (pour la génération des clés JWT)

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/JessyFra/Civilipedia_ClientAPI.git
cd Civilipedia_ClientAPI
```

---

### 2. Base de données

Ouvrez phpMyAdmin (ou un client MySQL) et importez le fichier SQL fourni : `civilipedia.sql`

Cela crée la base `civilipedia` et l'alimente avec des données de test (articles, utilisateurs, versions).

---

### 3. Configurer et lancer l'API

#### a) Installer les dépendances

```bash
cd civilipedia-api
composer install
```
> **Note :** Ne prettez pas attention aux erreurs.

#### b) Configurer les variables d'environnement

Copiez le fichier d'exemple et adaptez-le :

```bash
cp .env.example .env
```

Éditez `.env.local` et renseignez :

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/civilipedia?serverVersion=8.0&charset=utf8mb4"
JWT_PASSPHRASE=votre_passphrase_ici
```

> **Note :** remplacez `root:` par `root:votre_mot_de_passe` si votre MySQL en a un.

#### c) Générer les clés RSA pour JWT

Créez le dossier de destination, puis générez la paire de clés :

**Sur Linux / macOS :**
```bash
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:votre_passphrase_ici
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:votre_passphrase_ici
```

**Sur Windows (PowerShell) :**
```powershell
New-Item -ItemType Directory -Force config\jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:votre_passphrase_ici
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:votre_passphrase_ici
```

> La valeur de `votre_passphrase_ici` doit être identique à `JWT_PASSPHRASE` dans `.env.local`.

#### d) Lancer le serveur de l'API

```bash
php -S localhost:3000 -t public
```

L'API est accessible sur `http://localhost:3000`.

> La documentation interactive Swagger est disponible sur `http://localhost:3000/api/doc`.

---

### 4. Configurer et lancer le client

#### a) Configurer l'URL de l'API

Dans `civilipedia-client/.env`, vérifiez que l'URL pointe bien vers l'API :

```dotenv
API_URL=http://localhost:3000/api
```

#### b) Lancer le serveur client

```bash
cd civilipedia-client
php -S localhost:8000 -t public
```

Le client est accessible sur `http://localhost:8000`.

---

## Comptes de test

Les comptes suivants sont créés par le script SQL de seed :

| Rôle | Nom d'utilisateur | Mot de passe |
|---|---|---|
| Administrateur | `admin` | `admin` |
| Utilisateur | `jean_dupont` | `jean_dupont` |
| Utilisateur | `marie_martin` | `marie_martin` |
| Utilisateur | `pierre_duval` | `pierre_duval` |

---

## Structure du projet

```
civilipedia-api/
├── config/
│   └── jwt/               → Clés RSA (non versionnées)
│
├── private/
│   ├── articles/          → Dossier de stockage des images des articles
│   └── avatars/           → Dossier de stockage des avatars
│
├── src/
│   ├── Controller/        → ArticleController, AuthController, AdminController, ...
│   ├── Entity/            → Article, User, Ban, ArticleVersion, Contact, ...
│   └── Repository/        → Requêtes Doctrine
│
├── .env.example           → Variables d'environnement (template)
└── .gitignore             → Clés JWT et fichiers locaux exclus

civilipedia-client/
├── public/
│   ├── index.php          → Point d'entrée
│   ├── avatar.php
│   ├── assets/
│   │   ├── css/           → Styles BEM + design tokens
│   │   └── img/
│   │
│   └── src/               → Pages, layout, helpers PHP
│       ├── pages/         → home, article, login, register, profile, admin, ...
│       ├── layout/        → header.php, footer.php
│       ├── ApiClient.php  → Lien avec l'api
│       └── Auth.php
│
└── .env                   → URL de l'API

documentation/
└── documentation_utilisateur.pdf

civilipedia.sql            → Création des tables + données de test
```

---

## Documentation utilisateur

La documentation utilisateur complète (sans extrait de code, avec captures d'écran) est disponible dans :

```
documentation/documentation_utilisateur.pdf
```

Elle décrit toutes les fonctionnalités de l'application à destination d'un utilisateur non informaticien.