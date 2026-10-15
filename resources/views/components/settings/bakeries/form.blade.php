@props([
    'bakery',
    'managers',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
])

@php
    use App\Enums\BakeryStatus;

    $statusOptions = [];

    foreach (BakeryStatus::cases() as $status) {
        $statusOptions[$status->value] = $status->label();
    }

    $managerOptions = collect($managers)->pluck('name', 'id')->all();
    $logoUrl = $bakery->logoUrl();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6" enctype="multipart/form-data">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- Design System §62 : formulaire en page dédiée, sections claires,
         une seule action principale. --}}
    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-semibold text-ink">Identité du site</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field
                name="name"
                label="Nom de la boulangerie"
                :value="$bakery->name"
                :autofocus="true"
                required
            />

            <x-ui.form-field
                name="code"
                label="Code interne"
                :value="$bakery->code"
                help="Identifiant court et unique, utilisé dans les rapports."
                required
            />

            <x-ui.form-field name="address" label="Adresse" :value="$bakery->address" />

            <x-ui.form-field
                name="phone"
                label="Téléphone"
                :value="$bakery->phone"
                autocomplete="tel"
                inputmode="tel"
            />

            <x-ui.select
                name="manager_id"
                label="Responsable"
                :options="$managerOptions"
                :selected="$bakery->manager_id"
                placeholder="— Non défini —"
            />

            <x-ui.select
                name="status"
                label="Statut"
                :options="$statusOptions"
                :selected="$bakery->status"
                required
            />
        </div>
    </div>

    {{-- Le logo sert aussi d'icône à l'onglet du navigateur (ADR-032). --}}
    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-semibold text-ink">Logo et favicon</h2>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-card border border-line bg-subtle">
                @if ($logoUrl !== null)
                    <img src="{{ $logoUrl }}" alt="Logo de {{ $bakery->name }}" class="size-full object-cover">
                @else
                    <span class="text-2xl font-extrabold text-brand-700">{{ $bakery->initial() }}</span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <label for="field-logo" class="mb-1.5 block text-xs font-bold text-ink">Logo de la boulangerie</label>

                <input
                    id="field-logo"
                    name="logo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    class="block w-full text-sm text-ink file:mr-3 file:rounded-control file:border file:border-line file:bg-subtle file:px-3 file:py-2 file:text-xs file:font-bold file:text-ink hover:file:bg-line focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                >

                <p class="mt-1.5 text-[11px] text-soft">
                    JPEG, PNG, WebP ou GIF — 4 Mo maximum. Une image carrée convient mieux :
                    elle devient l’icône de l’onglet du navigateur et la marque de la barre latérale.
                </p>

                @error('logo')
                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror

                @if ($logoUrl !== null)
                    <label class="mt-3 flex items-center gap-2.5 text-sm">
                        <input type="hidden" name="remove_logo" value="0">
                        <input
                            type="checkbox"
                            name="remove_logo"
                            value="1"
                            class="size-4 rounded-control border-line-strong text-danger focus:ring-2 focus:ring-brand-100"
                        >
                        Supprimer le logo
                    </label>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-semibold text-ink">Observations</h2>

        <div class="mt-4">
            <label for="field-notes" class="block text-sm font-medium text-ink">Notes internes</label>

            <textarea
                id="field-notes"
                name="notes"
                rows="4"
                class="mt-1.5 block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-600 focus:ring-2 focus:ring-brand-600/25 focus:outline-none
                    {{ $errors->has('notes') ? 'border-danger' : 'border-line' }}"
                @if ($errors->has('notes')) aria-invalid="true" aria-describedby="field-notes-error" @endif
            >{{ old('notes', $bakery->notes) }}</textarea>

            @error('notes')
                <p id="field-notes-error" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger">
                    <x-ui.icon name="circle-alert" :size="16" class="mt-0.5 shrink-0" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('bakeries.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
