@props([
    'customer' => null,
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom / raison sociale" :value="$customer?->name" :autofocus="true" required />
            <x-ui.form-field name="phone" label="Téléphone" inputmode="tel" :value="$customer?->phone" />
            <x-ui.form-field name="email" label="Email" type="email" :value="$customer?->email" />
            <x-ui.form-field name="address" label="Adresse" :value="$customer?->address" />
        </div>

        <div class="mt-4">
            <label for="field-note" class="mb-1.5 block text-xs font-bold text-ink">Observations</label>

            <textarea
                id="field-note"
                name="note"
                rows="3"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('note') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('note')) aria-invalid="true" aria-describedby="field-note-error" @endif
            >{{ old('note', $customer?->note) }}</textarea>

            @error('note')
                <p id="field-note-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $customer?->is_active ?? true))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Client actif
        </label>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('customers.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
