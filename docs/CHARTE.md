# Charte graphique — BakeryOS (charte caisse)

> **À lire avant de créer ou modifier un écran, un composant ou un style.**
> Ce document est le contrat visuel du projet. Il est dérivé de
> `BakeryOS – Prototype (charte caisse).html` et il est **vérifié
> automatiquement** (voir §9).

---

## 1. Source unique de vérité

Toutes les couleurs, tous les rayons et toutes les ombres vivent dans
**`resources/css/app.css`**, dans le bloc `@theme` de Tailwind 4. Une vue
ne définit jamais une valeur visuelle : elle utilise une classe issue de
ces tokens.

> Règle d'or : **si le token n'existe pas, on l'ajoute dans `app.css`
> puis on l'utilise.** Jamais l'inverse.

---

## 2. Couleurs

| Rôle | Tokens | Usage |
|---|---|---|
| Primaire (vert) | `brand-50` → `brand-900` | actions, marque, états positifs. `brand-700` = bouton primaire, `brand-500` = item actif, `brand-50` = fond doux |
| Contextuel (bleu) | `context-50/100/500/600/700` | information, sélecteur de site, badges neutres |
| Surfaces | `surface`, `canvas`, `subtle`, `line`, `line-strong` | cartes (`surface`), fond de page (`canvas`), survol (`subtle`), bordures (`line`, `line-strong`) |
| Texte | `ink`, `muted`, `soft` | titres (`ink`), texte courant (`muted`), indications (`soft`) |
| États | `success`, `warning`, `danger`, `info`, `neutral` (+ `-bg`) | libellés d'état, alertes, badges |
| Croûte | `crust-100`, `crust-500` | dégradé des visuels produits de la caisse |

Exemples : `bg-surface`, `border-line`, `text-ink`, `text-muted`,
`bg-brand-700 hover:bg-brand-800`, `bg-context-600`, `bg-danger-bg text-danger`.

### Blanc et noir

- `text-white` est autorisé sur un fond coloré (bouton primaire, bandeau).
- `bg-white` est **interdit** → utiliser `bg-surface`.
- `bg-black` / `text-black` sont **interdits** → utiliser `bg-ink` / `text-ink`.

---

## 3. Typographie

- Famille unique : **Roboto** (`--font-sans`). Aucune autre police.
- Langue de l'interface : français.
- Classes utilitaires de la charte :
  - `label-caps` — petit libellé en majuscules espacées (`text-[11px] font-bold uppercase tracking-wide`) ;
  - `numeric` — chiffres alignés (`font-variant-numeric: tabular-nums`), **obligatoire** pour tout montant/quantité.

---

## 4. Rayons et ombres

| Token | Classe | Valeur | Usage |
|---|---|---|---|
| `--radius-control` | `rounded-control` | 4 px | boutons, champs, badges rectangulaires |
| `--radius-card` | `rounded-card` | 8 px | cartes, panneaux, menus |
| `--radius-modal` | `rounded-modal` | 8 px | modales |
| — | `rounded-full` | 999 px | pastilles, badges d'état |
| `--shadow-card` | `shadow-card` | ombre légère | cartes |
| `--shadow-elevated` | `shadow-elevated` | ombre portée | menus, modales, toasts |

Interdits : `rounded-[…]`, `shadow-[…]`, et les ombres par défaut
`shadow-sm/md/lg/xl/2xl/inner`.

---

## 5. Structure du shell

- **Sidebar** 256 px (`w-64`) : bandeau vert (`brand-700`), bloc « Site
  actif » bleu (`context-600`), menu en majuscules (`subtle` + `line` au
  survol, `brand-500` + blanc pour l'actif).
- **Topbar** 64 px, sticky : recherche, sélecteur de site (Livewire), cloche
  d'alertes, profil.
- **Contenu** : centré, `max-width: 1600px`, padding 24 px.
- **Barre d'accent** supérieure de 6 px (`brand-700`), rendue par
  `body::before` — ne jamais la dupliquer dans une vue.

---

## 6. Catalogue des composants

Tous les composants sont des composants Blade anonymes : `<x-ui.* />`.

| Composant | Rôle | Props principales |
|---|---|---|
| `<x-ui.icon name size stroke />` | icône Lucide inline | `name`, `size` |
| `<x-ui.button variant icon icon-after href loading size />` | bouton (majuscules, rayon 4 px) | `variant` = `primary`(défaut) `secondary` `soft` `blue` `danger` `ghost` |
| `<x-ui.status-badge status label tone icon />` | badge d'état (jamais la couleur seule) | `status` (config) ou `tone` + `label` |
| `<x-ui.stat-card label value unit trend trend-tone meta icon />` | carte KPI | — |
| `<x-ui.card title subtitle flush />` | carte/panneau (+ slots `actions`) | — |
| `<x-ui.data-table :headers="[…]" />` | tableau (thead auto, `tbody` en slot) | header = chaîne ou `['label' => …, 'align' => 'right']` |
| `<x-ui.search-input name placeholder />` | champ de recherche avec icône | — |
| `<x-ui.select name label :options placeholder />` | liste déroulante labellisée | — |
| `<x-ui.form-field name label type help required />` | champ de formulaire labellisé | — |
| `<x-ui.progress :value="0-100" tone />` | barre de progression | — |
| `<x-ui.bar-chart :values="[…]" height />` | histogramme CSS (valeurs 0-100) | — |
| `<x-ui.modal name title subtitle max-width />` | modale Alpine (+ slot `footer`) | ouverture : `$dispatch('open-modal', 'name')` |
| `<x-ui.alert tone title />` | message d'information/attention/erreur | — |
| `<x-ui.empty-state title description icon />` | état vide utile | — |
| `<x-ui.toast />` | notification éphémère (montée par le layout) | appel : `window.toast('…')` |
| `<x-layout.page-header eyebrow title description />` | en-tête de page (+ slot `actions`) | — |

Règles d'usage :

- **Une seule action primaire** par zone ; les autres en `secondary`.
- **Chaque écran** utilise `x-layout.page-header` et affiche le contexte de
  site (il l'est déjà via le shell).
- Les états `loading` / `empty` / `error` sont toujours gérés
  (`x-ui.button :loading`, `x-ui.empty-state`, `x-ui.alert`).
- Les toasts non bloquants passent par `window.toast(...)` côté Blade, ou
  `$this->dispatch('notify', message: '…')` dans un composant Livewire.
- Un **statut** se rend via `<x-ui.status-badge>` et la table
  `config('boulangerie.statuses')` — jamais une couleur seule.
- Un **montant** se rend via `App\Support\Format::money()` — jamais
  `number_format()`.

---

## 7. États de libellé

Un badge porte toujours un **libellé** en plus de la couleur (lisibilité,
daltonisme) :

`<x-ui.status-badge tone="warning" label="À valider" />`

Couleurs sémantiques : `success` (vert), `warning` (ambre), `danger`
(rouge), `info` (bleu), `neutral` (gris).

---

## 8. Interdits et alternatives

| Interdit | Alternative |
|---|---|
| `bg-[#35A64A]`, `#fff` dans une vue | token (`bg-brand-500`, `bg-surface`) |
| `bg-red-500`, `text-slate-700`, `border-gray-200` | `bg-danger`, `text-muted`, `border-line` |
| `bg-white`, `bg-black`, `text-black` | `bg-surface`, `bg-ink`, `text-ink` |
| `rounded-[9px]`, `rounded-t-[5px]` | `rounded-card`, `rounded-control`, `rounded-t-sm` |
| `shadow-sm`, `shadow-md`, `shadow-[…]` | `shadow-card`, `shadow-elevated` |
| `style="color: …"`, `style="background: …"` | classes de la charte |
| une seconde famille d'icônes | `<x-ui.icon>` (Lucide) |

---

## 9. Garde-fou automatique

La charte est **vérifiée**, pas seulement documentée :

```bash
php artisan charte:check      # liste les écarts
composer charte               # alias
composer ci                   # analyse + lint + charte + tests
```

Le scanner `App\Support\Charte\CharteScanner` parcourt toutes les vues
Blade et le JavaScript ; le test `CharteGuardTest` échoue dès qu'une
couleur, un rayon ou une ombre sort de la charte. Une dérive ne peut donc
pas être fusionnée.

---

## 10. Checklist avant de livrer un écran

- [ ] aucune couleur, rayon ou ombre en dur (`composer charte` vert) ;
- [ ] `x-layout.page-header` présent, une seule action primaire ;
- [ ] états `empty`, `loading`, `error` gérés ;
- [ ] montants/quantités via `Format`, chiffres en `numeric` ;
- [ ] icônes uniquement via `<x-ui.icon>` ;
- [ ] responsive 1440 / 1280 / 1024 / 768 / 390 ;
- [ ] l'écran et ses variantes sont couverts par un test de rendu.
