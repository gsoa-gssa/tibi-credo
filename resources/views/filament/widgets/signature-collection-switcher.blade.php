<div class="flex items-center space-x-2">
    <label for="signature-collection-switcher" class="text-sm font-medium">{{ __('signature_collection.name') }}</label>
    <select
        id="signature-collection-switcher"
        wire:model="selectedSignatureCollectionId"
        class="filament-forms-select block w-auto text-sm border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
    >
        @foreach($this->collections as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </select>
</div>
