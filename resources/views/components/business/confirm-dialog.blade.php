@props([
    'name',
    'title' => 'Confirmer l’opération',
    'impact' => null,
])

{{-- Boîte de confirmation avec impact affiché (Design System §77) : elle
     rappelle explicitement l'effet de l'action avant validation. --}}
<x-ui.modal :name="$name" :title="$title" max-width="max-w-md">
    @if ($impact)
        <x-ui.alert tone="warning" class="mb-4">{{ $impact }}</x-ui.alert>
    @endif

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ui.modal>
