<?php

declare(strict_types=1);

namespace App\Domain\Sites;

use App\Models\Bakery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Identité d'un site : création, modification et cycle de vie du logo
 * (cahier §19, ADR-032 et ADR-033).
 *
 * Seul point d'écriture de `bakeries` : les contrôleurs (administration des
 * sites et paramètres) délèguent ici, aucun ne manipule le logo lui-même.
 *
 * Le fichier vit sur le disque `public`, dossier `bakeries/` ; seul le
 * chemin est persisté. Un nouveau logo remplace l'ancien, effacé du disque
 * **après** l'enregistrement réussi — jamais avant, pour ne pas perdre
 * l'ancienne image si l'écriture échoue.
 */
final class BakeryProfileService
{
    private const LOGO_DIRECTORY = 'bakeries';

    /**
     * Crée un site et, le cas échéant, son logo.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?UploadedFile $logo = null): Bakery
    {
        unset($attributes['logo'], $attributes['remove_logo']);

        $attributes['logo_path'] = $this->storeLogo($logo);

        return Bakery::create($attributes);
    }

    /**
     * Met à jour l'identité d'un site.
     *
     * Un fichier envoyé remplace le logo ; sinon `$removeLogo` l'efface ;
     * sinon le logo en place est conservé.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        Bakery $bakery,
        array $attributes,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): Bakery {
        unset($attributes['logo'], $attributes['remove_logo']);

        $previousLogo = $bakery->logo_path;

        $uploaded = $this->storeLogo($logo);

        if ($uploaded !== null) {
            $attributes['logo_path'] = $uploaded;
        } elseif ($removeLogo) {
            $attributes['logo_path'] = null;
        }

        $bakery->update($attributes);

        if (array_key_exists('logo_path', $attributes) && $attributes['logo_path'] !== $previousLogo) {
            $this->deleteLogo($previousLogo);
        }

        return $bakery;
    }

    /**
     * Enregistre le logo envoyé et retourne son chemin, ou null s'il n'y a
     * pas de fichier.
     */
    private function storeLogo(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store(self::LOGO_DIRECTORY, 'public');
    }

    private function deleteLogo(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
