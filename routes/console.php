<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cahier §40 — scheduler : péremption (statuts, alertes, conversion des
// invendus en pertes) chaque heure juste après minuit puis toutes les
// heures ; les autres contrôles (commandes en retard, clôtures,
// statistiques) s'ajouteront au fil des phases.
Schedule::command('boulangerie:expiry-check')->hourlyAt(5);

// Cahier §48 / ADR-021 — les exports restent téléchargeables 7 jours :
// purge quotidienne des fichiers expirés et des échecs anciens.
Schedule::command('boulangerie:exports-prune')->dailyAt('03:15');
