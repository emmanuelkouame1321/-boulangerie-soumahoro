# Journal des décisions

Chaque décision structurante est consignée ici (format ADR court).
Une décision consignée ne peut être modifiée qu'en ajoutant une nouvelle
entrée qui la remplace.

---

## ADR-001 — Architecture frontend : Blade + Livewire

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier des charges laisse ouvert le choix entre
  Blade + Livewire et Inertia.
- **Décision :** Blade + Livewire 4. Pas de SPA, pas d'Inertia.
- **Justification :** ERP métier à formulaires denses et workflows
  transactionnels ; SSR, moins de JS à maintenir, toute la logique reste
  côté serveur — cohérent avec le principe « permissions contrôlées côté
  serveur » et « logique métier hors des composants UI ».
- **Conséquence :** les échanges riches passent par des composants
  Livewire, jamais par une API JS ad hoc.

---

## ADR-002 — Base de données : MySQL en développement

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier recommande PostgreSQL ; XAMPP (MySQL/MariaDB)
  est déjà installé sur le poste de développement.
- **Décision :** MySQL/MariaDB en développement
  (`boulangerie_soumahoro`), PostgreSQL visé en production.
- **Justification :** démarrer sans installation supplémentaire, tout en
  gardant un bloc PostgreSQL prêt à l'emploi dans `.env.example`.
- **Conséquence :** aucune requête SQL propriétaire PostgreSQL ; rester
  sur Eloquent et le Query Builder pour garantir la portabilité. Les
  tests tournent sur SQLite en mémoire (`phpunit.xml`).

---

## ADR-003 — Localisation : français, FCFA, Africa/Abidjan

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** `APP_LOCALE=fr`, `APP_FAKER_LOCALE=fr_FR`,
  `APP_TIMEZONE=Africa/Abidjan` (surcharge de `config/app.php`),
  devise unique FCFA sans décimale.
- **Conséquence :** tous les montants de l'interface passent par
  `App\Support\Format::money()`. Séparateur de milliers = espace
  insécable étroite, séparateur décimal = virgule.

---

## ADR-004 — Rôles et permissions : spatie/laravel-permission

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** `spatie/laravel-permission` 6. Trois rôles de départ
  (`super-admin`, `production-manager`, `sales-manager`) déclarés dans
  `config/boulangerie.php`.
- **Justification :** permissions fines configurables par le Super-Admin
  (cahier §20.4) sans réinventer un système maison.
- **Conséquence :** le wildcard du Super-Admin est porté par
  `Gate::before` dans `AppServiceProvider`, pas par une permission en
  base. Les alias de middleware `role`, `permission` et
  `role_or_permission` sont déclarés dans `bootstrap/app.php`.

---

## ADR-005 — Tokens du Design System portés par Tailwind 4 `@theme`

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** la palette, les rayons et les ombres du design system
  sont déclarés une seule fois dans `resources/css/app.css` via `@theme`.
  La convention `tailwind.config.js` du §58 du design system est adaptée
  à la génération de thème de Tailwind 4.
- **Conséquence :** interdiction absolue d'écrire une couleur, un rayon ou
  une ombre en dur dans une vue. Ajouter le token puis l'utiliser.

---

## ADR-006 — Configuration métier centralisée

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** `config/boulangerie.php` porte les valeurs d'amorçage,
  les listes fermées (types de mouvement, statuts, unités, modes de
  paiement) et le plan de rôles. `config/navigation.php` porte le menu.
- **Justification :** cahier §53.7 — les paramètres métier ne doivent pas
  être codés en dur.
- **Conséquence :** ces valeurs deviendront progressivement
  administrables en base (Phase 1 et 9) ; le fichier de configuration
  restera la source des valeurs d'amorçage et des libellés.

---

---

## ADR-007 — Authentification maison plutôt que Breeze

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier des charges laisse le choix ; Breeze fournit un
  scaffolding d'authentification clé en main.
- **Décision :** authentification écrite à la main — `LoginController`,
  `LogoutController`, `PasswordResetLinkController`,
  `NewPasswordController` — sur le layout et les composants existants.
- **Justification :** Breeze installe son propre layout, ses propres vues
  et ses propres classes Tailwind, en contradiction avec le Design System
  (« ne pas créer de styles concurrents »). Le coût d'écriture est faible
  et le contrôle total.
- **Conséquence :** messages d'erreur en français dans `lang/fr/`. La
  limitation des tentatives est double : 5 par couple email + IP dans
  `LoginRequest`, 20/min par IP via le limiteur nommé `login`.

---

## ADR-008 — Isolation par site via un scope global Eloquent

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier impose « les sites sont isolés par défaut »
  (principe §53.8) sans préciser le mécanisme.
- **Décision :** trait `App\Models\Concerns\BelongsToBakery` + scope
  global `App\Models\Scopes\BakeryScope`, adossés à
  `App\Domain\Sites\BakeryContext` (singleton).
- **Comportement :**
  - Super-Admin sans site sélectionné → vision consolidée, pas de filtre ;
  - Super-Admin avec site sélectionné → filtré sur ce site ;
  - autre rôle → toujours filtré ; **si aucun site n'est résolu, le scope
    ferme l'accès (`1 = 0`) plutôt que de l'ouvrir** ;
  - hors requête HTTP authentifiée (CLI, queue, seeders) → pas de filtre,
    les traitements devant poser explicitement leur contexte.
- **Justification :** le repli fail-closed évite qu'une erreur de
  configuration expose les données d'un autre site. C'est le point le plus
  sensible du multi-site.
- **Conséquence :** `App\Support\Navigation` conserve temporairement un
  mode dégradé (menu complet sans utilisateur) tant que l'appelant n'est
  pas authentifié ; à retirer en Phase 1.5.

---

## ADR-009 — Composants Livewire classiques (pas mono-fichier)

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** Livewire 4 génère par défaut des composants mono-fichier
  dans `resources/views/components/`.
- **Décision :** utiliser `php artisan make:livewire <Module>/<Nom> --class`.
- **Justification :** composants testables avec `Livewire::test()`,
  cohérents avec le découpage `app/Livewire/<Module>/` déjà documenté, et
  sans ambiguïté avec les composants Blade anonymes qui occupent déjà
  `resources/views/components/`.
- **Conséquence :** ne pas laisser un `make:livewire` sans `--class`
  créer un fichier `⚡*.blade.php` dans `components/`.

---

## ADR-010 — Charte visuelle : BakeryOS (charte caisse)

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** l'ancien design system (`design_system_boulangerie_soumahoro.md`)
  et l'ancien prototype ont été retirés du dépôt au profit de
  `BakeryOS – Prototype (charte caisse).html`. Cette charte impose une
  palette verte (primaire) + bleue (contextuelle), Roboto, des boutons en
  majuscules à rayon 4 px, des cartes à rayon 8 px et une barre d'accent
  supérieure.
- **Décision :** la charte BakeryOS devient la source de vérité visuelle.
  Les tokens sont portés par `resources/css/app.css` (`@theme`), les
  composants par `resources/views/components/ui|layout`. La logique métier
  reste dans `app/Domain/` — la charte ne touche que la présentation.
- **Conséquence :** interdiction maintenue d'écrire une couleur, un rayon
  ou une ombre en dur. Le vocabulaire de tokens sémantiques (`canvas`,
  `surface`, `line`, `ink`, `brand`, `context`, `success`…) est conservé,
  seule leur valeur change.

---

## ADR-011 — Périmètre de l'intégration du prototype

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Décision :** l'intégration du prototype livre la charte, le shell et
  **tous** les écrans du prototype, ainsi que le module Caisse (POS)
  (`/caisse`). Les écrans sont des interfaces de référence protégées par
  permission ; leur branchement aux services métier suit la ROADMAP
  (achats Phase 3, production Phase 4, ventes Phase 5, etc.).
- **Justification :** le prototype définit la composition visuelle de
  chaque module ; livrer les écrans tôt fige la navigation, les
  permissions et les composants partagés, sans introduire de règle métier
  prématurée.
- **Conséquence :** `config/boulangerie.php` porte désormais un catalogue
  `permissions` (union des permissions de module) créé par le seeder ; un
  rôle `cashier` est ajouté. Le catalogue produits du POS est un jeu de
  démonstration en attendant le modèle `Product` (Phase 1.4) et le domaine
  Ventes (Phase 5).

---

## Hypothèses à confirmer

Le cahier des charges §54 liste 20 décisions métier à valider avec le
propriétaire du projet. Voir le tableau en fin de `ROADMAP.md` pour le
rattachement de chacune à une phase.

Tant qu'une question n'est pas tranchée :

1. utiliser la valeur par défaut de `config/boulangerie.php` ;
2. ne jamais la coder en dur dans un service ou une vue ;
3. consigner l'hypothèse retenue dans une nouvelle entrée ADR.

### Hypothèses actuellement retenues

| Sujet | Hypothèse par défaut | Statut |
|---|---|---|
| Stock négatif | interdit (`guards.allow_negative_stock = false`) | à confirmer |
| Clé d'idempotence | obligatoire sur les opérations critiques | à confirmer |
| Glace / fagot / sel | ressources cumulées en FCFA par sac | à confirmer |
| Durée limite de vente | configurable par produit | à confirmer |
| Modes de paiement | espèces, mobile money, virement, autre | à confirmer |
