@php
  $sheet_labels = $batch->sheetsHTMLString();
@endphp
<x-letters>
  <x-letter>
    <x-slot name="addressPosition">{{ $addressPosition }}</x-slot>
    <x-slot name="pp_postcode">{{ $batch->signatureCollection->pp_sender_zipcode }}</x-slot>
    <x-slot name="pp_place">{{ $batch->signatureCollection->getLocalized('pp_sender_place') }}</x-slot>
    <x-slot name="priorityMail">{{ $priorityMail ?? false }}</x-slot>
    <x-slot name="ppLine">
      {{ $batch->signatureCollection->getLocalized('pp_sender_name') }}
    </x-slot>
    <x-slot name="address">
      {!! $batch->commune->address !!}
    </x-slot>
    <x-slot name="datePlaceLine">
      {{ $batch->signatureCollection->getLocalized('pp_sender_place') }}, {{$batch->created_at->format("d.m.Y")}}
    </x-slot>
    <x-slot name="subjectLine">
      {!! __('letter.subject') !!}
    </x-slot>
    @if($batch->created_at->gt($batch->signatureCollection->end_date->subDays(20)))
      <p style="border: 5px solid black; padding: 5px;">
        <b style="font-size: 2rem; ">{{__('letter.end_date', ['date' => $batch->signatureCollection->end_date->format("d.m.Y")])}}</b>
        <br>
        @if($batch->created_at->lt(\Carbon\Carbon::create(2025, 12, 19)))
          Falls es nicht möglich ist, die bescheinigten Listen bis zum 19.12.2025 zu retournieren (A-Post, Zustellung bei uns Samstag 20.12.25), setzen Sie sich bitte mit uns in Kontakt.
        @endif
      </p>
    @endif
    <br>
    <p>
      {{ __('letter.greeting') }}
    </p>
    <p>
      {{ __('letter.intro.' . $batch->signatureCollection->type->value, ['sheets_count' => $batch->sheets_count, 'signature_count' => $batch->signature_count, 'name' => $batch->signatureCollection->getLocalized('official_name')]) }}
      @if($sheet_labels != '')
        Die Bögen haben folgende Referenznummern: {!! $sheet_labels !!}.
      @endif
    </p>
    <p>
      {{ __('letter.request', ['deadline' => $batch->expected_return_date->format("d.m.Y")]) }}
    </p>
    <p>
      @if($batch->signatureCollection->return_address_letters == $batch->signatureCollection->return_address_parcels)
        {{ __('letter.return_address_all') }}<br>
        {{ $batch->signatureCollection->return_address_letters }}
      @else
        {{ __('letter.return_address_letters') }}<br>
        {{ $batch->signatureCollection->return_address_letters }}<br><br>
        {{ __('letter.return_address_parcels') }}<br>
        {{ $batch->signatureCollection->return_address_parcels }}
      @endif
    </p>
    <p>
      {{ __('letter.ending' . $batch->signatureCollection->type->value) }}<br>
      {{ $batch->signatureCollection->getLocalized('responsible_person_name') }}<br>
      {{ $batch->signatureCollection->getLocalized('responsible_person_email') }}<br>
      {{ $batch->signatureCollection->getLocalized('responsible_person_phone') }}<br>
    </p>
  </x-letter>
</x-letters>