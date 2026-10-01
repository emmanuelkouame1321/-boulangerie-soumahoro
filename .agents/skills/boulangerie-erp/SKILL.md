---
name: boulangerie-erp
description: Conventions de développement de l'ERP Boulangerie Soumahoro (Laravel 12, Blade + Livewire 4, Tailwind 4, Spatie Permission). À charger avant toute modification de ce dépôt pour respecter les règles métier, l'architecture par services et le design system.
---

# Skill — ERP Boulangerie Soumahoro

## Quand l'utiliser

À chaque intervention sur ce dépôt : nouvelle fonctionnalité, correction,
migration, écran, composant UI.

## À lire avant d'agir

1. `AGENTS.md` — manuel d'exploitation et Definition of Done
2. `ROADMAP.md` — phase active
3. `docs/ARCHITECTURE.md` — couches et motifs obligatoires
4. Le paragraphe correspondant du `cahier_des_charges_gestion_boulangerie.md`
5. L'écran ou le composant correspondant dans
   `BakeryOS – Prototype (charte caisse).html` (charte visuelle)

## Règles de code à appliquer sans exception

- `declare(strict_types=1);` en tête de chaque fichier PHP applicatif.
- Toute règle métier va dans `app/Domain/<Module>/`, jamais dans un
  contrôleur, un composant Livewire ou une vue.
- Toute écriture de stock passe par `App\Domain\Inventory\StockMovementService`.
- Toute opération critique : `DB::transaction` + `lockForUpdate()` +
  clé d'idempotence.
- Les montants sont des entiers (FCFA) ou `decimal`, jamais des `float`.
- Les montants et quantités s'affichent via `App\Support\Format`.
- Aucune couleur, aucun rayon, aucune ombre en dur : uniquement les tokens
  de `resources/css/app.css`.
- Les statuts s'affichent via `<x-ui.status-badge />` — jamais une couleur
  seule.
- Les libellés métier viennent de `config/boulangerie.php`.
- L'autorisation est vérifiée côté serveur (Policy / middleware
  `permission:`), jamais seulement en masquant un bouton.

## Commandes à lancer avant de conclure

```bash
composer ci     # analyse + lint + test
```

## Tests à écrire

- **Calcul** (recette, rendement, stock, ristourne, perte, coût) → test
  unitaire.
- **Parcours** (achat, réception, production, vente, perte, transfert,
  permissions) → test fonctionnel.
- **Écran** → test de rendu HTTP.
- **Concurrence** (deux opérations sur le même stock) → test dédié.

## Pièges connus

- Ne pas modifier `quantity` d'un stock directement : créer un mouvement.
- Ne pas exécuter `env()` hors de `config/` (bloqué par Larastan) : passer
  par un fichier de configuration.
- Ne pas créer de seconde famille d'icônes : Lucide uniquement, via
  `<x-ui.icon />`.
- Ne pas mettre un formulaire métier long dans une modale : page dédiée.
- Ne pas afficher une erreur technique brute : message clair + code.
