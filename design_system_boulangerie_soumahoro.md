# BOULANGERIE SOUMAHORO --- DESIGN SYSTEM

## Design System produit officiel --- Laravel + Tailwind CSS

**Version :** 1.0\
**Statut :** Référence obligatoire pour l'implémentation UI/UX\
**Produit :** Boulangerie Soumahoro\
**Cible :** application web métier multi-boulangeries, multi-rôles,
stocks, production, ventes, grossistes, achats, paie et reporting.

> **Instruction à l'agent de codage :** ce document est la source de
> vérité UI/UX. Ne pas inventer de styles, composants, couleurs,
> espacements ou comportements concurrents sans raison documentée.
> Construire une interface cohérente, sobre, premium, extrêmement
> lisible et orientée opérations.

------------------------------------------------------------------------

# 1. Vision design

Boulangerie Soumahoro doit donner l'impression d'un **ERP métier
premium**, robuste et rassurant, sans devenir froid ou industriel.

Le design doit transmettre :

-   confiance ;
-   précision ;
-   fraîcheur ;
-   maîtrise opérationnelle ;
-   rapidité ;
-   simplicité ;
-   professionnalisme.

Le produit est utilisé par des personnes qui doivent prendre des
décisions rapidement : stock faible, production à lancer, commande à
réceptionner, ventes du jour, pertes, grossistes, charges.

## 1.1. Principes

### 1. Clarté avant décoration

Chaque écran doit répondre rapidement à :

> Où suis-je ? Que se passe-t-il ? Que dois-je faire ?

### 2. Les chiffres importants sont immédiatement visibles

Stocks, production, ventes, alertes et pertes doivent être hiérarchisés
visuellement.

### 3. Une action primaire par contexte

Éviter plusieurs boutons concurrents.

### 4. Les états métier sont explicites

Une commande « en attente », un stock « critique », une production «
validée » ou un lot « expiré » doivent être reconnaissables
instantanément.

### 5. Le système doit être calme

Les alertes importantes sont visibles, mais l'interface ne doit pas
devenir rouge partout.

### 6. L'interface doit être cohérente entre les sites

Changer de boulangerie ne doit pas changer le langage visuel.

------------------------------------------------------------------------

# 2. Identité visuelle

## 2.1. Positionnement

**Nom :** Boulangerie Soumahoro

**Signature UX interne :**

> Produire juste. Vendre mieux. Piloter simplement.

Cette signature peut apparaître sur l'écran de connexion ou dans la
documentation, mais ne doit pas encombrer l'application.

## 2.2. Direction artistique

Style :

-   premium ;
-   chaleureux ;
-   moderne ;
-   minimaliste ;
-   fonctionnel ;
-   légèrement inspiré de l'univers boulangerie ;
-   sans textures décoratives lourdes ;
-   sans gradients excessifs ;
-   sans effet « dashboard crypto ».

L'interface doit ressembler davantage à un **excellent logiciel métier
SaaS** qu'à un site vitrine de boulangerie.

------------------------------------------------------------------------

# 3. Palette de couleurs

La palette doit être construite autour d'un **brun cacao profond**, d'un
**crème chaud** et d'un **ambre pain**.

## 3.1. Tokens de couleur

Utiliser des variables CSS, puis les exposer dans Tailwind.

``` css
:root {
    --color-brand-50:  #FFF8ED;
    --color-brand-100: #FCEFD9;
    --color-brand-200: #F7DDB3;
    --color-brand-300: #EFC27E;
    --color-brand-400: #DFA04D;
    --color-brand-500: #C98332;
    --color-brand-600: #A96325;
    --color-brand-700: #874B20;
    --color-brand-800: #66361D;
    --color-brand-900: #432318;

    --color-surface:    #FFFFFF;
    --color-background: #F8F7F4;
    --color-subtle:     #F1EFEA;
    --color-border:     #E6E1D8;

    --color-text:       #211E1A;
    --color-text-muted: #706B63;
    --color-text-soft:  #99938A;

    --color-success:    #2F7D52;
    --color-success-bg: #EAF6EE;

    --color-warning:    #B7791F;
    --color-warning-bg: #FFF5DD;

    --color-danger:     #C84A4A;
    --color-danger-bg:  #FDECEC;

    --color-info:       #3F6FA3;
    --color-info-bg:    #EAF2FB;
}
```

## 3.2. Usage

### Brand

Utiliser `brand-600` pour :

-   boutons primaires ;
-   liens actifs ;
-   focus ;
-   éléments d'identité.

### Brand foncé

Utiliser `brand-800` / `brand-900` pour :

-   sidebar ;
-   logo ;
-   textes de marque.

### Success

Uniquement pour :

-   validé ;
-   disponible ;
-   terminé ;
-   payé ;
-   réception confirmée.

### Warning

Pour :

-   stock faible ;
-   attente ;
-   bientôt expiré ;
-   attention.

### Danger

Pour :

-   stock critique ;
-   erreur ;
-   perte ;
-   suppression ;
-   opération irréversible.

### Info

Pour :

-   information ;
-   commande en cours ;
-   état informatif ;
-   aide.

**Règle :** ne jamais utiliser une couleur d'état uniquement pour
transmettre une information. Toujours afficher un texte, une icône ou
les deux.

------------------------------------------------------------------------

# 4. Dark mode

Le dark mode n'est pas obligatoire dans le MVP.

Si implémenté :

-   ne pas inverser simplement toutes les couleurs ;
-   conserver les contrastes ;
-   éviter un noir pur ;
-   utiliser un fond `#171614` ;
-   surfaces autour de `#211F1B` ;
-   bordures discrètes ;
-   brand légèrement éclairci.

Le mode clair reste le mode principal.

------------------------------------------------------------------------

# 5. Typographie

## 5.1. Police

Priorité :

``` text
Inter, ui-sans-serif, system-ui, sans-serif
```

Pour les chiffres financiers et métriques, utiliser également Inter avec
`font-variant-numeric: tabular-nums`.

## 5.2. Échelle

  Token       Taille Usage
  --------- -------- ------------------------------
  display       32px titre dashboard exceptionnel
  h1            28px titre page
  h2            22px section
  h3            18px sous-section
  body-lg       16px contenu important
  body          14px interface standard
  body-sm       13px secondaire
  caption       12px metadata
  micro         11px usage exceptionnel

Poids :

-   400 : normal ;
-   500 : medium ;
-   600 : semibold ;
-   700 : bold.

Éviter le 800 sauf branding très ponctuel.

------------------------------------------------------------------------

# 6. Spacing system

Utiliser exclusivement l'échelle Tailwind autant que possible.

Base :

``` text
4px
8px
12px
16px
20px
24px
32px
40px
48px
64px
```

## Règles

-   padding carte standard : `p-5` ou `p-6` ;
-   gap entre sections : `gap-6` ;
-   gap entre champs : `gap-4` ;
-   padding boutons : `px-4 py-2.5` ;
-   rayon standard : `rounded-xl`.

Éviter l'accumulation de petits espacements incohérents.

------------------------------------------------------------------------

# 7. Border radius

``` text
xs : 6px
sm : 8px
md : 10px
lg : 12px
xl : 16px
2xl: 20px
```

Standard :

-   inputs : `rounded-lg` ;
-   boutons : `rounded-lg` ;
-   cards : `rounded-xl` ;
-   modales : `rounded-2xl`.

Ne pas utiliser des cartes très arrondies façon application mobile.

------------------------------------------------------------------------

# 8. Ombres

Utiliser très peu d'ombres.

``` text
shadow-xs : séparation légère
shadow-sm : carte surélevée
shadow-md : dropdown/modal
shadow-lg : modal majeure
```

Le design doit principalement utiliser :

-   surfaces ;
-   bordures ;
-   hiérarchie typographique.

------------------------------------------------------------------------

# 9. Layout global

## 9.1. Desktop

Structure :

``` text
┌────────────────────────────────────────────────────────────┐
│ Sidebar │ Topbar                                           │
│         ├──────────────────────────────────────────────────┤
│         │ Breadcrumb / contexte                             │
│         │                                                   │
│         │ Titre + actions                                  │
│         │                                                   │
│         │ Contenu principal                                │
│         │                                                   │
│         │                                                   │
└─────────┴───────────────────────────────────────────────────┘
```

Sidebar :

-   largeur : 248px ;
-   fixe sur desktop ;
-   possibilité de collapse à 72px.

Topbar :

-   hauteur : 64px ;
-   sticky ;
-   fond surface ;
-   bordure inférieure.

Zone contenu :

``` css
max-width: 1600px;
margin-inline: auto;
padding: 24px;
```

## 9.2. Mobile

Sidebar devient :

-   drawer ;
-   menu accessible via bouton ;
-   bottom navigation uniquement si nécessaire pour les actions
    critiques.

Le contenu doit rester utilisable à une main pour les opérations
simples.

------------------------------------------------------------------------

# 10. Sidebar

## 10.1. Structure

``` text
[Logo] Boulangerie
       SOUMAHORO

Vue d'ensemble

OPÉRATIONS
  Production
  Stocks
  Achats
  Ventes

CLIENTS
  Grossistes
  Particuliers

GESTION
  Pertes
  Charges
  Employés
  Paie

ANALYSE
  Rapports

ADMINISTRATION
  Boulangeries
  Utilisateurs
  Paramètres
  Audit
```

Les groupes non autorisés ne sont jamais affichés.

## 10.2. Item actif

-   fond brand-50 ;
-   texte brand-800 ;
-   indicateur vertical 3px brand-600 ;
-   icône brand-600.

Pas de gros bloc coloré.

------------------------------------------------------------------------

# 11. Topbar

Contenu :

``` text
[☰]  Boulangerie actuelle ▾     Recherche...     🔔  Avatar
```

Éléments :

1.  sélecteur de boulangerie ;
2.  recherche globale ;
3.  notifications ;
4.  profil.

Pour le Super-Admin :

``` text
Toutes les boulangeries ▾
```

Pour un responsable :

``` text
Boulangerie Cocody ▾
```

------------------------------------------------------------------------

# 12. Sélecteur de boulangerie

C'est un composant critique.

Affichage :

``` text
🏪 Boulangerie Cocody
   Abidjan • Active
```

Le changement de site doit être clairement confirmé si des données
sensibles sont visibles.

Ne jamais changer silencieusement de site pendant une saisie en cours.

------------------------------------------------------------------------

# 13. Page header

Standard :

``` text
Stocks
Gérez les matières premières et produits finis.

[Exporter] [Nouvel ajustement]
```

Structure :

-   breadcrumb optionnel ;
-   H1 ;
-   description courte ;
-   actions à droite.

Une seule action principale doit avoir le style primaire.

------------------------------------------------------------------------

# 14. Cards

## Card standard

``` text
┌──────────────────────────────┐
│ STOCK FARINE                 │
│                              │
│ 24 sacs                      │
│ +4 depuis hier               │
│                              │
│ Seuil : 10                   │
└──────────────────────────────┘
```

Structure :

-   label petit en uppercase léger ;
-   valeur grande ;
-   tendance ;
-   metadata.

## KPI card

Hauteur recommandée : 132--156px.

Ne pas surcharger les KPI avec trop de données.

------------------------------------------------------------------------

# 15. Dashboard Super-Admin

Ordre visuel recommandé :

## Ligne 1 --- KPIs

-   CA du jour ;
-   ventes du jour ;
-   production du jour ;
-   stock critique ;
-   pertes du jour.

## Ligne 2

``` text
Production vs ventes
        +
Alertes opérationnelles
```

## Ligne 3

``` text
Stocks critiques
        +
Performance par boulangerie
```

## Ligne 4

``` text
Grossistes
        +
Charges
```

## Règle

Le dashboard ne doit jamais afficher 15 graphiques de même importance.

Maximum recommandé :

-   5 KPI cards ;
-   2 graphiques principaux ;
-   2 listes opérationnelles ;
-   1 tableau synthétique.

------------------------------------------------------------------------

# 16. Dashboard Production

Priorité :

``` text
Production du jour
│
├── lots prévus
├── lots réalisés
├── rendement
└── écarts
```

Puis :

-   matières critiques ;
-   production en cours ;
-   consommation ;
-   alertes.

CTA principal :

``` text
+ Nouvelle production
```

------------------------------------------------------------------------

# 17. Dashboard Vente

Priorité :

-   ventes du jour ;
-   produits disponibles ;
-   grossistes servis ;
-   ventes particuliers ;
-   restes ;
-   pertes.

CTA :

``` text
+ Nouvelle vente
```

------------------------------------------------------------------------

# 18. Couleurs des statuts

Créer un composant unique `StatusBadge`.

  Statut       Couleur   Exemple
  ------------ --------- --------------
  actif        success   Actif
  validé       success   Validé
  disponible   success   Disponible
  en attente   warning   En attente
  faible       warning   Stock faible
  critique     danger    Critique
  expiré       danger    Expiré
  annulé       danger    Annulé
  brouillon    neutral   Brouillon
  en cours     info      En cours
  livré        success   Livré

Toujours afficher le libellé.

------------------------------------------------------------------------

# 19. Boutons

## Primary

Usage :

-   créer ;
-   valider ;
-   confirmer une action métier principale.

``` text
bg-brand-600
hover:bg-brand-700
text-white
```

## Secondary

``` text
bg-white
border-border
text-text
hover:bg-subtle
```

## Ghost

Pour actions secondaires.

## Danger

Uniquement pour action réellement destructive.

Exemple :

``` text
Supprimer
```

Jamais utiliser danger pour une simple action de clôture ou de
validation.

## Icon button

Toujours fournir :

-   tooltip desktop ;
-   aria-label ;
-   zone tactile minimale 40×40px.

------------------------------------------------------------------------

# 20. États des boutons

Chaque bouton doit posséder :

-   default ;
-   hover ;
-   focus-visible ;
-   active ;
-   disabled ;
-   loading.

Loading :

``` text
[spinner] Validation...
```

Pendant une opération critique, empêcher le double clic.

------------------------------------------------------------------------

# 21. Formulaires

Les formulaires métier sont essentiels.

## Champ

``` text
Nom du produit
[________________________]
Description secondaire
```

Label toujours visible.

Ne jamais dépendre uniquement du placeholder.

## Erreur

``` text
Quantité produite
[ -5 ]

⚠ La quantité doit être supérieure à 0.
```

## Aide

Afficher une aide sous le champ seulement si nécessaire.

------------------------------------------------------------------------

# 22. Formulaire de production

L'écran doit être conçu comme un assistant opérationnel.

## Étape 1 --- Sélection

``` text
Produit fini
[ Pain                ▾ ]

Recette
[ Pain — 1 sac        ▾ ]

Quantité de lots
[ 5 ]
```

## Étape 2 --- Prévisualisation

``` text
MATIÈRES NÉCESSAIRES

Farine       5 sacs      ✓ Disponible
Améliorant   5 paquets   ✓ Disponible
Levure       5 paquets   ⚠ Stock faible
```

## Étape 3 --- Rendement

``` text
Rendement théorique
1 500 pains

Quantité réellement produite
[ 1 482 ]

Écart
-18
```

## Étape 4 --- Confirmation

Afficher un résumé avant validation.

------------------------------------------------------------------------

# 23. Stock : design opérationnel

## Table

Colonnes :

``` text
Produit
Catégorie
Stock actuel
Unité
Seuil
En commande
Statut
Actions
```

## Quantité

Les nombres doivent être alignés à droite.

Utiliser `tabular-nums`.

## Stock critique

Afficher :

``` text
7 sacs
Critique
```

et non uniquement une couleur.

------------------------------------------------------------------------

# 24. Fiche produit

Structure :

``` text
Produit
─────────────────────────────
Nom
Type
Catégorie
Unité

STOCK
─────────────────────────────
Site       Stock   Seuil   Statut

FOURNISSEURS
─────────────────────────────

PRIX
─────────────────────────────

PRODUCTION
─────────────────────────────
Recettes associées
Rendement
Durée limite

HISTORIQUE
─────────────────────────────
```

Utiliser des tabs si la page devient trop longue.

------------------------------------------------------------------------

# 25. Table design

La table est un composant central.

## Règles

-   header `bg-subtle` léger ;
-   hauteur ligne 56px environ ;
-   hover subtil ;
-   bordures horizontales ;
-   pas de grille lourde ;
-   pagination claire ;
-   sélection de ligne seulement si utile.

## Actions

Préférer :

``` text
Voir
Modifier
Plus ▾
```

plutôt que 5 boutons visibles.

------------------------------------------------------------------------

# 26. Mobile tables

Sur mobile :

-   transformer les lignes complexes en cards ;
-   conserver les informations prioritaires ;
-   déplacer les actions dans un menu.

Exemple :

``` text
Farine 50 kg
24 sacs · Seuil 10
✓ Disponible

[Voir]
```

------------------------------------------------------------------------

# 27. Alertes

Le système d'alerte doit avoir trois niveaux :

### Information

Bleu discret.

### Attention

Ambre.

### Critique

Rouge réservé aux problèmes nécessitant une action.

Une alerte critique doit indiquer :

1.  quoi ;
2.  où ;
3.  pourquoi ;
4.  action recommandée.

Exemple :

``` text
Stock critique
Farine 50 kg — Boulangerie Cocody

Stock : 7 sacs
Seuil : 10 sacs

[Voir le stock] [Créer une commande]
```

------------------------------------------------------------------------

# 28. Notifications

Panel :

``` text
Notifications
────────────────────────

● Stock critique
  Farine — 7 sacs
  Il y a 5 min

● Commande reçue
  Fournisseur ABC
  Il y a 32 min

○ Production validée
  1 482 pains
  Il y a 1 h
```

Filtres :

-   Toutes ;
-   Non lues ;
-   Critiques.

------------------------------------------------------------------------

# 29. Modales

Utiliser une modale seulement pour :

-   confirmation ;
-   petite saisie ;
-   aperçu ;
-   action courte.

Ne jamais mettre un formulaire métier complexe dans une modale.

Pour une production, réception ou commande complète : utiliser une page
dédiée.

------------------------------------------------------------------------

# 30. Confirmation d'actions critiques

Format :

``` text
Valider la production ?

Cette action va :

• consommer 5 sacs de farine
• consommer 5 paquets d'améliorant
• créer 1 500 pains théoriques
• créer un nouveau lot

[Annuler] [Valider la production]
```

L'utilisateur doit comprendre l'impact avant confirmation.

------------------------------------------------------------------------

# 31. Drawer

Utiliser un drawer pour :

-   détail rapide ;
-   aperçu d'un stock ;
-   détail d'une alerte ;
-   historique court.

Pas pour les workflows longs.

------------------------------------------------------------------------

# 32. Empty states

Un empty state doit être utile.

Exemple :

``` text
Aucune production aujourd'hui

Aucune production n'a encore été enregistrée
pour cette boulangerie.

[Créer une production]
```

Éviter les illustrations géantes.

------------------------------------------------------------------------

# 33. Loading states

Utiliser :

-   skeletons pour les pages ;
-   spinner pour les actions ;
-   progress indicator pour les opérations longues.

Ne jamais afficher une page vide pendant le chargement.

------------------------------------------------------------------------

# 34. Toasts

Durée :

-   succès : 4 s ;
-   information : 5 s ;
-   warning : 6 s ;
-   erreur : jusqu'à fermeture ou 8 s.

Exemples :

``` text
✓ Réception validée
25 sacs ont été ajoutés au stock.
```

Erreur :

``` text
Impossible de valider la production.
Le stock de farine est insuffisant.
```

------------------------------------------------------------------------

# 35. Recherche globale

Raccourci :

``` text
Ctrl + K
```

Recherche :

-   produit ;
-   fournisseur ;
-   grossiste ;
-   commande ;
-   production ;
-   vente ;
-   employé.

Résultats groupés :

``` text
Produits
Fournisseurs
Productions
Ventes
```

------------------------------------------------------------------------

# 36. Breadcrumbs

Exemple :

``` text
Production / Ordres / PROD-2026-0042
```

Utiliser uniquement lorsque la profondeur dépasse 1 niveau.

------------------------------------------------------------------------

# 37. Pagination

Toujours afficher :

``` text
1–25 sur 248 résultats

‹ 1 2 3 4 … 10 ›
```

Possibilité :

``` text
25 / 50 / 100 par page
```

------------------------------------------------------------------------

# 38. Filtres

Le pattern standard :

``` text
[Rechercher...] [Site ▾] [Statut ▾] [Période ▾] [Plus de filtres]
```

Les filtres actifs apparaissent sous forme de chips :

``` text
Site: Cocody ×
Statut: Critique ×
```

------------------------------------------------------------------------

# 39. Charts

Les graphiques doivent rester simples.

Types recommandés :

-   line chart : évolution ;
-   bar chart : comparaison ;
-   donut : répartition simple ;
-   area chart : tendance.

Éviter les 3D charts.

Les données doivent toujours avoir :

-   légende ;
-   unités ;
-   période ;
-   tooltip ;
-   état sans données.

------------------------------------------------------------------------

# 40. Dashboard stock

Graphiques :

### Évolution des stocks

Courbe sur 7/30 jours.

### Consommation

Barres par matière.

### Répartition

Donut uniquement pour une répartition simple.

------------------------------------------------------------------------

# 41. Dashboard production

Graphiques :

-   production théorique vs réelle ;
-   rendement ;
-   pertes ;
-   consommation de matières.

Toujours afficher la période.

------------------------------------------------------------------------

# 42. Dashboard ventes

Graphiques :

-   ventes quotidiennes ;
-   CA par produit ;
-   CA grossistes vs particuliers ;
-   évolution mensuelle.

------------------------------------------------------------------------

# 43. Grossistes

La fiche grossiste doit être orientée décision.

Header :

``` text
Entreprise / Nom
Grossiste actif

Téléphone
Zone

[Créer une vente] [Voir historique]
```

KPIs :

-   quantité annuelle ;
-   CA annuel ;
-   moyenne quotidienne ;
-   ristourne estimée/configurée.

Tabs :

``` text
Vue d'ensemble
Ventes
Statistiques
Ristournes
Paiements
Historique
```

------------------------------------------------------------------------

# 44. Ristournes

Présenter les règles sans ambiguïté.

``` text
Période : 2026

Quantité cumulée
186 400 pains

Palier applicable
[ règle configurée ]

Base éligible
...

Ristourne calculée
...
```

Ne pas utiliser de visualisation agressive.

------------------------------------------------------------------------

# 45. Production : lots et péremption

Une fiche lot :

``` text
LOT #PAIN-2026-00421

Production
30/09/2026 05:42

Produit
Pain

Produit
1 500 unités

Vendu
1 285

Restant
215

Date limite
30/09/2026 23:59

Statut
⚠ À écouler
```

Si expiré :

``` text
Statut
✕ Expiré
```

------------------------------------------------------------------------

# 46. Pertes

Les pertes doivent être visuellement sérieuses sans dramatisation.

KPI :

-   pertes du jour ;
-   pertes du mois ;
-   valeur estimée ;
-   top produits.

Table :

``` text
Date
Produit
Lot
Quantité
Cause
Valeur
Site
```

------------------------------------------------------------------------

# 47. Achats et réceptions

Workflow visuel :

``` text
Brouillon → En attente → Commandée → Livrée → Validée
```

Utiliser un stepper horizontal sur desktop.

Sur mobile :

``` text
● Validée
│
● Livrée
│
○ Commandée
│
○ En attente
```

------------------------------------------------------------------------

# 48. Employés

Fiche employé :

``` text
Avatar / initiales
Nom complet
Poste
Site
Statut

Salaire
Contrat
Historique paie
```

Les informations sensibles doivent être masquées selon les permissions.

------------------------------------------------------------------------

# 49. Paie

La paie doit privilégier la confidentialité.

Éviter d'afficher des salaires dans des dashboards généraux.

Le tableau doit afficher :

``` text
Employé
Période
Salaire brut
Retenues
Net
Statut
```

Accès strictement permissionné.

------------------------------------------------------------------------

# 50. Charges

Catégories :

-   électricité ;
-   eau ;
-   imprévus ;
-   autres.

KPI :

-   charges du jour ;
-   charges du mois ;
-   évolution ;
-   répartition.

------------------------------------------------------------------------

# 51. Multi-sites

Toujours afficher le contexte du site.

Exemple :

``` text
Boulangerie Cocody
```

sur les écrans opérationnels.

Pour le Super-Admin :

``` text
Toutes les boulangeries
```

Un rapport consolidé doit permettre de distinguer :

``` text
Total
Cocody
Yopougon
Abobo
...
```

------------------------------------------------------------------------

# 52. Permissions UX

Une fonctionnalité interdite ne doit pas simplement provoquer une
erreur.

Si possible :

-   masquer l'action ;
-   ou la désactiver avec explication si sa présence est nécessaire.

Exemple :

``` text
[Modifier la recette]  ← visible seulement si permission
```

Ne jamais compter uniquement sur l'UI : l'autorisation serveur reste
obligatoire.

------------------------------------------------------------------------

# 53. Accessibilité

Objectif : WCAG 2.2 AA autant que possible.

Obligatoire :

-   contraste suffisant ;
-   focus visible ;
-   navigation clavier ;
-   labels explicites ;
-   `aria-label` pour icônes ;
-   messages d'erreur associés aux champs ;
-   ne jamais communiquer un statut uniquement par couleur.

Cibles tactiles :

``` text
minimum 40×40px
```

Préférer 44×44px sur mobile.

------------------------------------------------------------------------

# 54. Iconographie

Utiliser une seule famille d'icônes.

Recommandation :

**Lucide Icons**

Style :

-   stroke ;
-   1.75--2px ;
-   tailles 16, 18, 20, 24.

Exemples :

``` text
Package      stock
Factory      production
ShoppingCart achats
Receipt      ventes
Users        employés
Building2    boulangeries
Bell         notifications
AlertTriangle alertes
TrendingUp   statistiques
Settings     paramètres
```

Ne pas mélanger plusieurs bibliothèques d'icônes.

------------------------------------------------------------------------

# 55. Avatar

Pour les utilisateurs :

-   photo si disponible ;
-   sinon initiales.

Tailles :

``` text
24px
32px
40px
48px
```

------------------------------------------------------------------------

# 56. Illustrations

Les illustrations sont secondaires.

Ne pas transformer le logiciel en interface marketing.

Les illustrations sont autorisées uniquement pour :

-   onboarding ;
-   empty states majeurs ;
-   écran de connexion.

------------------------------------------------------------------------

# 57. Écran de connexion

Direction :

``` text
┌─────────────────────────────────────────────────────┐
│                                                     │
│      [Logo]                                         │
│      BOULANGERIE SOUMAHORO                          │
│                                                     │
│      Bienvenue                                      │
│      Connectez-vous à votre espace de gestion.     │
│                                                     │
│      Email / identifiant                            │
│      [____________________________]                 │
│                                                     │
│      Mot de passe                                   │
│      [____________________________]                 │
│                                                     │
│      [ Se connecter ]                               │
│                                                     │
└─────────────────────────────────────────────────────┘
```

Fond crème très léger.

------------------------------------------------------------------------

# 58. Design tokens Tailwind

Recommandation de configuration :

``` js
theme: {
  extend: {
    colors: {
      brand: {
        50:  '#FFF8ED',
        100: '#FCEFD9',
        200: '#F7DDB3',
        300: '#EFC27E',
        400: '#DFA04D',
        500: '#C98332',
        600: '#A96325',
        700: '#874B20',
        800: '#66361D',
        900: '#432318',
      },
      surface: '#FFFFFF',
      canvas: '#F8F7F4',
      border: '#E6E1D8',
      ink: '#211E1A',
      muted: '#706B63',
      success: '#2F7D52',
      warning: '#B7791F',
      danger: '#C84A4A',
      info: '#3F6FA3',
    },
    borderRadius: {
      card: '12px',
      modal: '16px',
    },
    boxShadow: {
      card: '0 1px 2px rgba(33, 30, 26, 0.04)',
      elevated: '0 8px 24px rgba(33, 30, 26, 0.10)',
    },
  },
}
```

Adapter les valeurs aux conventions de la version Tailwind effectivement
utilisée.

------------------------------------------------------------------------

# 59. Composants UI à créer

Créer une bibliothèque interne réutilisable.

## Layout

-   `AppShell`
-   `Sidebar`
-   `Topbar`
-   `PageHeader`
-   `Breadcrumbs`
-   `SiteSwitcher`

## Navigation

-   `NavItem`
-   `NavGroup`
-   `Tabs`
-   `Pagination`

## Data display

-   `StatCard`
-   `DataTable`
-   `StatusBadge`
-   `Metric`
-   `ProgressBar`
-   `Timeline`
-   `EmptyState`

## Forms

-   `TextInput`
-   `NumberInput`
-   `Select`
-   `Combobox`
-   `DatePicker`
-   `DateRangePicker`
-   `Textarea`
-   `Checkbox`
-   `Radio`
-   `Switch`
-   `FormField`
-   `FormError`

## Feedback

-   `Toast`
-   `Alert`
-   `ConfirmDialog`
-   `Modal`
-   `Drawer`
-   `Skeleton`
-   `Spinner`
-   `Tooltip`

## Business components

-   `StockStatus`
-   `StockMovementBadge`
-   `ProductionStatus`
-   `BatchStatus`
-   `OrderStatus`
-   `PaymentStatus`
-   `ExpiryIndicator`
-   `QuantityInput`
-   `ProductSelector`
-   `RecipePreview`
-   `ProductionSummary`
-   `AlertCard`
-   `WholesalerSummary`
-   `RebateProgress`
-   `SiteStatus`

------------------------------------------------------------------------

# 60. Conventions de composants

Chaque composant doit :

1.  avoir une API claire ;
2.  accepter des variantes contrôlées ;
3.  gérer disabled/loading/error ;
4.  être accessible ;
5.  être responsive ;
6.  ne pas contenir de logique métier ;
7.  être réutilisable.

Exemple :

``` text
<StatusBadge
    status="critical"
    label="Stock critique"
/>
```

Ne pas créer :

``` text
<RedThingForFlourStock />
```

------------------------------------------------------------------------

# 61. Architecture UI recommandée

``` text
resources/
├── css/
│   └── app.css
├── views/
│   ├── components/
│   │   ├── ui/
│   │   ├── layout/
│   │   └── business/
│   ├── layouts/
│   ├── dashboard/
│   ├── production/
│   ├── inventory/
│   ├── purchasing/
│   ├── sales/
│   ├── wholesalers/
│   ├── losses/
│   ├── employees/
│   ├── payroll/
│   ├── expenses/
│   ├── reports/
│   └── settings/
```

Si Livewire est utilisé :

``` text
app/Livewire/
├── Dashboard/
├── Production/
├── Inventory/
├── Purchasing/
├── Sales/
├── Wholesalers/
├── Employees/
└── ...
```

------------------------------------------------------------------------

# 62. Design des écrans CRUD

Pattern standard :

``` text
PageHeader
    ↓
Filters / Search
    ↓
DataTable
    ↓
Pagination
```

Création :

``` text
PageHeader
    ↓
Form Card
    ↓
Actions
```

Détail :

``` text
PageHeader
    ↓
Summary
    ↓
Tabs
    ↓
Content
```

------------------------------------------------------------------------

# 63. Responsive breakpoints

Utiliser les breakpoints Tailwind :

``` text
sm 640
md 768
lg 1024
xl 1280
2xl 1536
```

Règles :

### \< 768px

-   sidebar drawer ;
-   cards empilées ;
-   tables transformées ;
-   actions principales pleine largeur si nécessaire.

### 768--1024px

-   sidebar compacte ;
-   grilles 2 colonnes.

### \> 1024px

-   layout complet ;
-   tables ;
-   dashboards multi-colonnes.

------------------------------------------------------------------------

# 64. Internationalisation

Le français est la langue par défaut.

Préparer les chaînes pour une future traduction :

``` text
resources/lang/fr/
resources/lang/en/
```

Les dates et nombres doivent respecter les conventions locales
configurées.

Devise par défaut :

``` text
FCFA / XOF
```

Le format doit être centralisé, pas écrit manuellement dans les vues.

------------------------------------------------------------------------

# 65. Formatage des nombres

Quantités :

``` text
1 500
24
7,5
```

Monétaire :

``` text
125 000 FCFA
```

Ne jamais afficher :

``` text
125000
```

dans une interface destinée à l'utilisateur.

------------------------------------------------------------------------

# 66. États métier visuels

### Stock

``` text
Disponible   ✓
Faible       !
Critique     !
Rupture      ×
```

### Production

``` text
Brouillon
Planifiée
En cours
Terminée
Annulée
```

### Commande

``` text
Brouillon
En attente
Commandée
Partiellement livrée
Livrée
Validée
Annulée
```

### Lot

``` text
Actif
À écouler
Expiré
Épuisé
```

------------------------------------------------------------------------

# 67. UX de clôture quotidienne

La clôture de vente doit être un écran guidé :

``` text
1. Ventes
2. Restes
3. Pertes
4. Écarts
5. Confirmation
```

Afficher les incohérences avant validation.

Exemple :

``` text
⚠ Écart détecté

Pain
Théorique restant : 20
Déclaré : 15
Écart : -5

[Justifier l'écart]
```

------------------------------------------------------------------------

# 68. UX des inventaires

Utiliser un mode « comptage rapide ».

``` text
Farine 50 kg
Stock système : 24
Compté : [ 23 ]

[Suivant]
```

Le clavier et le scanner éventuel doivent être pris en compte pour
accélérer la saisie.

------------------------------------------------------------------------

# 69. UX des alertes stock

Chaque alerte doit fournir un chemin d'action.

``` text
Stock critique
Farine 50 kg
7 sacs disponibles

Actions :
[Créer commande]
[Voir historique]
[Ignorer]
```

« Ignorer » doit nécessiter une permission et éventuellement une
justification.

------------------------------------------------------------------------

# 70. UX des achats

Lorsqu'un produit atteint son seuil :

``` text
Farine
Stock : 7
Seuil : 10

Suggestion :
Commander 20 sacs

[Créer une commande]
```

La suggestion est une aide : elle ne doit pas déclencher automatiquement
un achat sans configuration explicite.

------------------------------------------------------------------------

# 71. UX de production et stock

Avant validation :

``` text
Impact sur le stock

Farine       -5
Améliorant   -5
Levure       -5

Produit fini

Pain        +1 500
```

C'est un élément obligatoire de confirmation pour les opérations
importantes.

------------------------------------------------------------------------

# 72. UX de vente

Une vente doit être rapide :

``` text
Produit
[ Pain             ]

Quantité
[ 300 ]

Prix unitaire
[ 250 FCFA ]

Total
75 000 FCFA

Client
[ Grossiste X     ]

[ Enregistrer la vente ]
```

Pour les grossistes réguliers, proposer les produits/quantités habituels
comme raccourcis.

------------------------------------------------------------------------

# 73. UX grossiste

Afficher les habitudes sans automatiser silencieusement.

``` text
Commande habituelle
Pain : 300/jour
Croissant : 50/jour

[Utiliser les quantités habituelles]
```

------------------------------------------------------------------------

# 74. UX permissions

Dans les paramètres :

``` text
Rôle
Responsable Production

Production
☑ Voir
☑ Créer
☑ Modifier
☑ Valider

Stocks
☑ Voir
☑ Inventaire
☐ Ajustement critique

Paie
☐ Voir
☐ Modifier
```

Les permissions critiques doivent être clairement distinguées.

------------------------------------------------------------------------

# 75. Micro-interactions

Utiliser de petites animations :

-   100--180 ms ;
-   ease-out ;
-   pas d'animation permanente.

Exemples :

-   ouverture menu ;
-   apparition toast ;
-   changement statut ;
-   expansion accordéon.

Respecter `prefers-reduced-motion`.

------------------------------------------------------------------------

# 76. Règles anti-pattern

Interdit :

-   gradients omniprésents ;
-   ombres lourdes ;
-   boutons géants partout ;
-   texte en majuscules excessif ;
-   10 couleurs concurrentes ;
-   icônes de styles différents ;
-   cartes imbriquées sans nécessité ;
-   tableaux illisibles ;
-   chiffres sans unités ;
-   couleur comme unique indication ;
-   popups pour chaque action ;
-   animation décorative ;
-   dashboard surchargé.

------------------------------------------------------------------------

# 77. Performance UI

L'interface doit :

-   éviter les composants lourds inutiles ;
-   charger les graphiques à la demande si nécessaire ;
-   paginer les gros tableaux ;
-   utiliser skeletons ;
-   éviter les requêtes répétitives ;
-   ne pas recharger toute la page pour une petite action Livewire.

Objectif :

> sensation de réponse immédiate pour les opérations courantes.

------------------------------------------------------------------------

# 78. Architecture des pages principales

## `/dashboard`

Dashboard selon rôle.

## `/production`

Liste des productions.

## `/production/create`

Création production.

## `/production/{id}`

Détail production.

## `/inventory`

Stocks.

## `/inventory/movements`

Mouvements.

## `/purchasing/orders`

Commandes.

## `/purchasing/receipts`

Réceptions.

## `/sales`

Ventes.

## `/wholesalers`

Grossistes.

## `/wholesalers/{id}`

Fiche grossiste.

## `/losses`

Pertes.

## `/employees`

Employés.

## `/payroll`

Paie.

## `/expenses`

Charges.

## `/reports`

Rapports.

## `/settings`

Paramètres.

------------------------------------------------------------------------

# 79. Navigation par rôle

## Super-Admin

Afficher tous les modules autorisés.

## Responsable Production

Navigation prioritaire :

``` text
Dashboard
Production
Stocks
Achats / besoins
Alertes
Rapports production
```

## Responsable Vente

``` text
Dashboard
Ventes
Grossistes
Particuliers
Produits finis
Restes / pertes
Rapports vente
```

Le menu doit refléter le travail réel de chaque rôle.

------------------------------------------------------------------------

# 80. Design des erreurs système

Erreur serveur :

``` text
Une erreur est survenue.

L'opération n'a pas été enregistrée.
Aucune donnée n'a été modifiée.

[Réessayer]
```

Ne jamais exposer une stack trace en production.

------------------------------------------------------------------------

# 81. Confirmation de succès

Le succès doit confirmer l'effet réel.

Mauvais :

``` text
✓ Succès
```

Bon :

``` text
✓ Production validée

1 482 pains ajoutés au stock.
5 sacs de farine consommés.
```

------------------------------------------------------------------------

# 82. Design de l'audit

L'audit est une timeline :

``` text
30 sept. 2026 · 08:42

Jean Kouassi
a validé la production PROD-0042

Stock :
Farine -5 sacs
Pain +1 482 unités
```

Chaque entrée doit permettre de voir le détail.

------------------------------------------------------------------------

# 83. Design du sélecteur de période

Présélections :

``` text
Aujourd'hui
Hier
7 derniers jours
30 derniers jours
Ce mois
Mois précédent
Année
Personnalisé
```

------------------------------------------------------------------------

# 84. Design des exports

Bouton :

``` text
Exporter ▾
```

Menu :

``` text
Excel
CSV
PDF
```

Pour les gros exports, afficher :

``` text
Export en préparation...
Vous recevrez une notification lorsqu'il sera disponible.
```

------------------------------------------------------------------------

# 85. Design de la recherche vide

``` text
Aucun résultat

Aucun produit ne correspond à « farine 100 ».

[Effacer la recherche]
```

------------------------------------------------------------------------

# 86. Design des permissions et sécurité visuelle

Les données sensibles :

-   salaire ;
-   paie ;
-   prix d'achat ;
-   marge ;
-   audit ;

doivent avoir une hiérarchie visuelle discrète et ne pas être affichées
dans des cartes publiques ou des aperçus non autorisés.

------------------------------------------------------------------------

# 87. Règles pour l'agent de codage

L'agent doit :

1.  créer les tokens avant les écrans ;
2.  créer les composants UI de base avant les pages métier ;
3.  ne jamais dupliquer un style déjà présent ;
4.  utiliser les composants partagés ;
5.  centraliser les couleurs ;
6.  centraliser les statuts ;
7.  centraliser le formatage des nombres et monnaies ;
8.  tester chaque composant en responsive ;
9.  tester les états loading/error/empty ;
10. vérifier l'accessibilité ;
11. ne pas mettre de logique métier dans les composants visuels ;
12. respecter les permissions ;
13. afficher le contexte de boulangerie ;
14. rendre les actions critiques explicites ;
15. utiliser les mêmes conventions sur tous les modules.

------------------------------------------------------------------------

# 88. Ordre d'implémentation UI

## Sprint UI 1 --- Fondations

-   fonts ;
-   couleurs ;
-   tokens ;
-   layout ;
-   sidebar ;
-   topbar ;
-   boutons ;
-   inputs ;
-   badges ;
-   tables ;
-   modales ;
-   toasts.

## Sprint UI 2 --- Dashboard

-   dashboard Super-Admin ;
-   dashboard Production ;
-   dashboard Vente ;
-   KPI ;
-   graphiques ;
-   alertes.

## Sprint UI 3 --- Opérations

-   stock ;
-   production ;
-   achats ;
-   réception ;
-   ventes.

## Sprint UI 4 --- Clients

-   grossistes ;
-   particuliers ;
-   ristournes.

## Sprint UI 5 --- Administration

-   employés ;
-   paie ;
-   charges ;
-   sites ;
-   utilisateurs ;
-   permissions.

## Sprint UI 6 --- Qualité

-   responsive ;
-   accessibilité ;
-   loading ;
-   erreurs ;
-   empty states ;
-   micro-interactions ;
-   cohérence visuelle.

------------------------------------------------------------------------

# 89. Checklist de validation visuelle

Avant de considérer une page terminée :

### Identité

-   [ ] palette respectée ;
-   [ ] typographie respectée ;
-   [ ] espacements cohérents ;
-   [ ] icônes cohérentes.

### UX

-   [ ] action principale évidente ;
-   [ ] contexte de site visible ;
-   [ ] états métier visibles ;
-   [ ] erreurs compréhensibles ;
-   [ ] feedback après action.

### Responsive

-   [ ] 1440px ;
-   [ ] 1280px ;
-   [ ] 1024px ;
-   [ ] 768px ;
-   [ ] 390px.

### Accessibilité

-   [ ] focus clavier ;
-   [ ] contraste ;
-   [ ] labels ;
-   [ ] aria ;
-   [ ] couleur non utilisée seule.

### Performance

-   [ ] skeleton ;
-   [ ] pagination ;
-   [ ] pas de rechargement inutile ;
-   [ ] graphiques raisonnables.

------------------------------------------------------------------------

# 90. Critère final de qualité

Une interface Boulangerie Soumahoro est réussie lorsque :

> un utilisateur qui ne connaît pas encore parfaitement le logiciel peut
> comprendre immédiatement son contexte, son stock, ses alertes et son
> action suivante sans avoir à « décoder » l'interface.

Le design doit donner une sensation de :

**ordre + confiance + vitesse + précision + simplicité.**

------------------------------------------------------------------------

# 91. Directive finale pour l'implémentation

Ce Design System doit être considéré comme une **couche fondamentale du
produit**, au même niveau que l'architecture backend.

Ne pas commencer par dessiner chaque écran indépendamment.

Construire d'abord :

``` text
DESIGN TOKENS
      ↓
UI PRIMITIVES
      ↓
COMPOSANTS
      ↓
PATTERNS MÉTIER
      ↓
LAYOUTS
      ↓
PAGES
      ↓
DASHBOARDS
```

Le résultat attendu est une interface cohérente dans laquelle les
modules **Production, Stocks, Achats, Ventes, Grossistes, Pertes,
Employés, Paie, Charges, Rapports et Administration** semblent
appartenir au même logiciel et au même système de conception.

**Boulangerie Soumahoro doit être reconnaissable par sa cohérence, pas
par une décoration excessive.**
