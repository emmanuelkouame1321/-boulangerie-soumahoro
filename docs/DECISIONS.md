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
