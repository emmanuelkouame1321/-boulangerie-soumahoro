# Boulangerie Soumahoro — Logiciel de gestion

ERP métier multi-sites pour boulangerie : achats, stock matières, recettes,
production, produits finis et lots, ventes, pertes, grossistes et
ristournes, employés, paie, charges, rapports et audit.

> **Produire juste. Vendre mieux. Piloter simplement.**

---

## Documentation de référence

| Fichier | Rôle |
|---|---|
| [`cahier_des_charges_gestion_boulangerie.md`](cahier_des_charges_gestion_boulangerie.md) | Règles métier, données, workflows, architecture — **source de vérité n° 1** |
| [`BakeryOS – Prototype (charte caisse).html`](BakeryOS%20%E2%80%93%20Prototype%20(charte%20caisse).html) | Charte visuelle de référence : tokens, composants, écrans, caisse |
| [`docs/CHARTE.md`](docs/CHARTE.md) | Contrat visuel : tokens, catalogue de composants, règles vérifiées automatiquement |
| [`AGENTS.md`](AGENTS.md) | Manuel d'exploitation pour le développement automatisé |
| [`ROADMAP.md`](ROADMAP.md) | Plan de développement par phases |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Découpage des couches et motifs obligatoires |
| [`docs/DECISIONS.md`](docs/DECISIONS.md) | Journal des décisions et hypothèses ouvertes |
| [`docs/SAUVEGARDES.md`](docs/SAUVEGARDES.md) | Sauvegardes, rétention, restauration testée et reprise après incident |

---

## Stack

- **PHP 8.2+** · **Laravel 12**
- **Blade + Livewire 4** (pas de SPA)
- **Tailwind CSS 4** — les tokens de la charte BakeryOS vivent dans
  `resources/css/app.css`
- **MySQL / MariaDB** en développement (PostgreSQL visé en production)
- **spatie/laravel-permission** pour rôles et permissions
- **PHPUnit 11**, **Larastan 3**, **Laravel Pint**

---

## Prérequis

- PHP 8.2 ou supérieur avec les extensions `pdo_mysql`, `mbstring`,
  `openssl`, `intl`
- Composer 2
- Node.js 20+ et npm
- MySQL / MariaDB (XAMPP fonctionne)

---

## Installation

```bash
composer setup
```

Cette commande installe les dépendances PHP et JS, crée le `.env`, génère
la clé d'application, joue les migrations et les seeders, puis construit
les assets.

### Manuellement

```bash
composer install
cp .env.example .env
php artisan key:generate

# Créer la base
mysql -u root -e "CREATE DATABASE boulangerie_soumahoro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate --seed
npm install && npm run build

# Lien public des photos de produits et des logos de site
# (le dossier /public/storage est ignoré par git)
php artisan storage:link
```

> **Images invisibles ?** Les photos de produits (`storage/app/public/products/`)
et les logos de site (`storage/app/public/bakeries/`) sont servis via
`public/storage`. Ce lien est **absolu** : il casse dès que le dossier du
projet change de nom ou d'emplacement, et toutes les images renvoient alors
un 404 sans le moindre message d'erreur. Après un déplacement ou un clone :

```bash
rm public/storage && php artisan storage:link
```

---

## Lancer le projet

```bash
composer dev
```

Démarre en parallèle le serveur HTTP, la queue, les logs (`pail`) et Vite.

L'application est ensuite disponible sur <http://localhost:8000>.

### Comptes de démonstration (environnement local uniquement)

Un compte par niveau, tous avec le mot de passe `password`, rattachés par
defaut à la boulangerie de Cocody :

| Email | Mot de passe | Rôle |
|---|---|---|
| `admin@soumahoro.test` | `password` | Super-Admin (tous les sites) |
| `production@soumahoro.test` | `password` | Responsable production |
| `vente@soumahoro.test` | `password` | Responsable vente |
| `caisse@soumahoro.test` | `password` | Caissier |

Le compte Super-Admin est personnalisable via `SUPER_ADMIN_EMAIL` et
`SUPER_ADMIN_PASSWORD` dans `.env`. Tous ces comptes ne sont **jamais**
créés hors `local` / `testing`.

Pour régénérer les comptes de démonstration :

```bash
php artisan db:seed --class=Database\\Seeders\\DemoUsersSeeder --force
```

---

## Commandes

| Commande | Effet |
|---|---|
| `composer dev` | Serveur + queue + logs + Vite |
| `composer test` | Suite de tests |
| `composer analyse` | Analyse statique (Larastan niveau 5) |
| `composer charte` | Vérifie que les vues respectent la charte (tokens uniquement) |
| `composer lint` | Vérification du style (Pint) |
| `composer format` | Correction automatique du style |
| `composer ci` | `analyse` + `lint` + `charte` + `test` — à lancer avant de conclure |
| `npm run build` | Assets de production |
| `npm run dev` | Vite en mode watch |

---

## Structure

```
app/
├── Domain/        règles métier, un dossier par module
├── Enums/         types fermés (Role, ProductType, StockMovementType)
├── Livewire/      composants Livewire (sans logique métier)
├── Models/        Eloquent
├── Support/       Format, Navigation
└── ...

config/
├── boulangerie.php   valeurs métier configurables — à ne pas coder en dur
└── navigation.php    menu applicatif

resources/
├── css/app.css               tokens de la charte BakeryOS (source unique)
├── js/app.js
└── views/
    ├── components/ui/        primitives
    ├── components/layout/    AppShell, sidebar, topbar, page-header
    ├── components/business/  StockStatus, StockMovementBadge, ExpiryIndicator
    ├── components/layouts/   gabarit HTML
    └── <module>/             pages
```

---

## Principes non négociables

1. Une seule source de vérité pour le stock — tout passe par un mouvement.
2. Toute variation de stock est traçable et jamais supprimée.
3. Les opérations critiques sont transactionnelles et idempotentes.
4. Les permissions sont contrôlées côté serveur.
5. Les sites sont isolés par défaut.
6. Les paramètres métier ne sont jamais codés en dur.
7. La logique métier n'est jamais dans une vue ni dans un composant UI.

Détail complet dans [`AGENTS.md`](AGENTS.md) §7 et
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

---

## Contribuer / poursuivre le développement

Le projet est conçu pour avancer **phase par phase, de façon autonome** :

1. ouvrir [`ROADMAP.md`](ROADMAP.md) ;
2. prendre la première case non cochée ;
3. suivre la boucle de travail de [`AGENTS.md`](AGENTS.md) §5 ;
4. cocher la case et continuer.

Chaque session doit se terminer avec `composer ci` vert.
