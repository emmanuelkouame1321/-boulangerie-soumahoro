@props([
    'product',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'groupOptions' => [],
    'typesByGroup' => [],
    'unitOptions' => [],
    'categoryOptions' => [],
    'alertModeOptions' => [],
])

@php
    use App\Enums\ProductType;

    // Le seuil est un decimal ; on retire les zéros inutiles pour la saisie.
    $threshold = $product->alert_threshold;
    $threshold = $threshold === null ? null : rtrim(rtrim((string) $threshold, '0'), '.');

    // Famille et nature initiales : on repart des saisies précédentes en
    // cas d'erreur de validation, sinon du modèle.
    $currentType = $product->type instanceof ProductType ? $product->type : ProductType::FinishedGood;
    $initialType = (string) old('type', $currentType->value);
    $initialGroup = (string) old('product_group', $currentType->group());
    $initialSku = (string) old('sku', $product->sku ?? '');
    $initialName = (string) old('name', $product->name ?? '');
    $photoUrl = $product->photoUrl();
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-6"
    x-data="{
        group: {{ Illuminate\Support\Js::from($initialGroup) }},
        type: {{ Illuminate\Support\Js::from($initialType) }},
        sku: {{ Illuminate\Support\Js::from($initialSku) }},
        name: {{ Illuminate\Support\Js::from($initialName) }},
        generating: false,
        selectGroup(group) {
            this.group = group;
            if (group === '{{ ProductType::FinishedGood->group() }}') {
                this.type = '{{ ProductType::FinishedGood->value }}';
            } else if (! (this.type in {{ Illuminate\Support\Js::from($typesByGroup['materials'] ?? []) }})) {
                this.type = '{{ ProductType::RawMaterial->value }}';
            }
        },
        async generateSku() {
            this.generating = true;
            try {
                const url = '{{ route('products.generate-sku') }}'
                    + '?type=' + encodeURIComponent(this.type)
                    + '&name=' + encodeURIComponent(this.name);
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (response.ok) {
                    const data = await response.json();
                    this.sku = data.sku;
                }
            } finally {
                this.generating = false;
            }
        }
    }"
>
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="field-name" class="mb-1.5 block text-xs font-bold text-ink">
                    Nom
                    <span class="text-danger" aria-hidden="true">*</span>
                </label>

                <input
                    id="field-name"
                    name="name"
                    type="text"
                    value="{{ $initialName }}"
                    x-model="name"
                    autofocus
                    required
                    class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none {{ $errors->has('name') ? 'border-danger' : '' }}"
                    @if ($errors->has('name')) aria-invalid="true" aria-describedby="field-name-error" @endif
                >

                @error('name')
                    <p id="field-name-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="field-sku" class="mb-1.5 block text-xs font-bold text-ink">
                    Code produit (SKU)
                    <span class="text-danger" aria-hidden="true">*</span>
                </label>

                <div class="flex items-center gap-2">
                    <input
                        id="field-sku"
                        name="sku"
                        type="text"
                        value="{{ $initialSku }}"
                        x-model="sku"
                        required
                        class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none {{ $errors->has('sku') ? 'border-danger' : '' }}"
                        @if ($errors->has('sku')) aria-invalid="true" aria-describedby="field-sku-error" @endif
                    >

                    <x-ui.button type="button" variant="secondary" size="sm" icon="refresh-cw" @click="generateSku()" x-bind:disabled="generating || name.trim() === ''">
                        Générer
                    </x-ui.button>
                </div>

                <p class="mt-1.5 text-[11px] text-soft">Identifiant unique et stable. Générez-le automatiquement si nécessaire.</p>

                @error('sku')
                    <p id="field-sku-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <fieldset>
                <legend class="mb-1.5 block text-xs font-bold text-ink">
                    Famille
                    <span class="text-danger" aria-hidden="true">*</span>
                </legend>

                <div class="inline-flex flex-wrap gap-1 rounded-control border border-line bg-surface p-1">
                    @foreach ($groupOptions as $value => $label)
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="product_group"
                                value="{{ $value }}"
                                :checked="group === '{{ $value }}'"
                                @change="selectGroup('{{ $value }}')"
                                class="sr-only"
                            >
                            <span
                                class="inline-flex items-center rounded-control px-4 py-2 text-[13px] font-medium transition"
                                :class="group === '{{ $value }}' ? 'bg-brand-700 text-white' : 'text-muted hover:bg-subtle hover:text-ink'"
                            >{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Le type réellement enregistré suit la famille et, pour les
                     matières premières, la nature choisie ci-dessous. --}}
                <input type="hidden" name="type" value="{{ $initialType }}" :value="type">

                @error('type')
                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-ui.select
                name="product_category_id"
                label="Catégorie"
                :options="$categoryOptions"
                :selected="$product->product_category_id"
                placeholder="— Non classé —"
            />

            <div x-show="group === 'materials'" x-cloak>
                <label for="field-nature" class="mb-1.5 block text-xs font-bold text-ink">Nature (matières premières)</label>

                <select
                    id="field-nature"
                    x-model="type"
                    class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                >
                    @foreach ($typesByGroup['materials'] ?? [] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label for="field-description" class="mb-1.5 block text-xs font-bold text-ink">Description</label>

            <textarea
                id="field-description"
                name="description"
                rows="3"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('description') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('description')) aria-invalid="true" aria-describedby="field-description-error" @endif
            >{{ old('description', $product->description) }}</textarea>

            @error('description')
                <p id="field-description-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Photo</h2>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-card border border-line bg-subtle">
                @if ($photoUrl !== null)
                    <img src="{{ $photoUrl }}" alt="" class="size-full object-cover">
                @else
                    <x-ui.icon name="image" :size="24" class="text-soft" />
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <label for="field-photo" class="mb-1.5 block text-xs font-bold text-ink">Illustration du produit</label>

                <input
                    id="field-photo"
                    name="photo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    class="block w-full text-sm text-ink file:mr-3 file:rounded-control file:border file:border-line file:bg-subtle file:px-3 file:py-2 file:text-xs file:font-bold file:text-ink hover:file:bg-line focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                >

                <p class="mt-1.5 text-[11px] text-soft">JPEG, PNG, WebP ou GIF — 4 Mo maximum. Sans image, la caisse affiche l’icône de la famille.</p>

                @error('photo')
                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                @enderror

                @if ($photoUrl !== null)
                    <label class="mt-3 flex items-center gap-2.5 text-sm">
                        <input type="hidden" name="remove_photo" value="0">
                        <input
                            type="checkbox"
                            name="remove_photo"
                            value="1"
                            class="size-4 rounded-control border-line-strong text-danger focus:ring-2 focus:ring-brand-100"
                        >
                        Supprimer la photo
                    </label>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Unités, seuils et durée de vente</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select name="primary_unit_id" label="Unité principale" :options="$unitOptions" :selected="$product->primary_unit_id" required />
            <x-ui.select
                name="secondary_unit_id"
                label="Unité secondaire"
                :options="$unitOptions"
                :selected="$product->secondary_unit_id"
                placeholder="— Aucune —"
                help="Conversion gérée dans les unités (ex. 1 sac = 50 kg)."
            />

            <x-ui.form-field
                name="alert_threshold"
                label="Seuil d’alerte"
                type="number"
                step="any"
                inputmode="decimal"
                :value="$threshold"
                help="Quantité minimale avant alerte."
            />

            <x-ui.select name="alert_mode" label="Mode d’alerte" :options="$alertModeOptions" :selected="$product->alert_mode" required />

            <x-ui.form-field
                name="shelf_life_days"
                label="Durée limite de vente (jours)"
                type="number"
                inputmode="numeric"
                :value="$product->shelf_life_days"
                help="Laisser vide si sans objet."
            />
        </div>

        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $product->is_active ?? true))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Produit actif
        </label>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Prix (FCFA)</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.form-field name="purchase_price" label="Prix d’achat" type="number" inputmode="numeric" :value="$product->purchase_price ?? 0" required />
            <x-ui.form-field name="sale_price" label="Prix de vente" type="number" inputmode="numeric" :value="$product->sale_price ?? 0" required />
            <x-ui.form-field name="wholesale_price" label="Prix grossiste" type="number" inputmode="numeric" :value="$product->wholesale_price ?? 0" required />
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('products.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
