<x-letter addressPosition="right">
  <x-slot name="ppLine">
    Allianz für ein Atomwaffenverbot,<br>
                PF 1069, 8031 Zürich</br>
  </x-slot>
  <x-slot name="address">
    {{ $contact->firstname }} {{ $contact->lastname }}
    <br>
    {{ $contact->street_no }}
    <br>
    @if (is_null($contact->zipcode))
        @php
            throw new \Exception('Zipcode is null');
        @endphp
    @else
        {{ $contact->zipcode->code }} {{ $contact->zipcode->name }}
    @endif
  </x-slot>
  <x-slot name="datePlaceLine">
    {{ __('contact.letter.place') }}, {{ \Carbon\Carbon::now()->format("d.m.Y") }}
  </x-slot>
  <x-slot name="subjectLine">
    @php
      $subjectField = 'subject_' . $language;
      $subjectText = $contact->contactType->$subjectField ?? 'TODO NO SUBJECT GIVEN';
      if (!empty($replacementDict)) {
        $subjectText = str_replace(array_keys($replacementDict), array_values($replacementDict), $subjectText);
      }
      echo $subjectText;
    @endphp
  </x-slot>
  <p>
    @php
      $bodyField = 'body_' . $language;
      $bodyText = $contact->contactType->$bodyField ?? 'TODO NO BODY GIVEN';
      if (!empty($replacementDict)) {
      $bodyText = str_replace(array_keys($replacementDict), array_values($replacementDict), $bodyText);
      }
      echo $bodyText;
    @endphp
  </p>
</x-letter>