@props([
    'name',
    'label' => 'Produit',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Sélectionner un produit',
    'help' => null,
    'required' => false,
])

@php
    // Sélecteur de produit : liste plate « nom (SKU) » fournie par le
    // contrôleur. Aucune requête ici : la logique reste serveur.
@endphp

<x-ui.select
    :name="$name"
    :label="$label"
    :options="$options"
    :selected="$selected"
    :placeholder="$placeholder"
    :help="$help"
    :required="$required"
    {{ $attributes }}
/>
