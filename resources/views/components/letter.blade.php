@props([
  'addressPosition' => 'left',
  'pp_postcode' => null,
  'pp_place' => null,
  // priorityMail must be 'A' or 'B'
  'priorityMail' => 'B'
])

@if (!in_array($addressPosition, ['left', 'right']))
    @php
        throw new \InvalidArgumentException("addressPosition must be 'left' or 'right'");
    @endphp
@endif

@if (!in_array($priorityMail, ['A', 'B']))
  @php
    throw new \InvalidArgumentException("priorityMail must be 'A' or 'B'");
  @endphp
@endif

@if (is_null($pp_postcode) || !preg_match('/^\d{4}$/', $pp_postcode))
    @php
        throw new \InvalidArgumentException("pp_postcode is required and must be exactly 4 digits");
    @endphp
@endif

@if (is_null($pp_place) || trim($pp_place) === '')
    @php
        throw new \InvalidArgumentException("pp_place is required and cannot be empty");
    @endphp
@endif

<x-slot name="css">
  @if(isset($css))
    {{ $css }}
  @endif
</x-slot>
<div class="letter-content" style="width: 210mm;">
  <div class="workaround">.</div>
  <div class="address-block {{ $addressPosition == 'right' ? 'address-block-right' : 'address-block-left' }}">
    <div>
      <div style="position: relative; border-bottom: 1px solid black; margin-bottom: 3mm; font-family: Arial, Verdana, Helvetica, sans-serif; font-size: 8pt;">
        <div style="position: absolute; top: 0; right: 0; font-size: 6pt;">
          {{__('letter.post_company') }}
        </div>
        <div data-bwip-datamatrix="123456789" data-bwip-width="12mm" data-bwip-height="12mm" style="position: absolute; top: 10mm; right: 0;"></div>
        <b style="font-size:12pt;">
          P.P.
        </b>
        @if ($priorityMail == 'A')
          <b style="font-size:24pt; line-height: 0.5em;">
            A
          </b>
        @endif
        {{ $pp_postcode }} {{ $pp_place }}
        <br>
        <div style="letter-spacing: -0.1mm;">
          {{ $ppLine }}
        </div>
      </div>
      <p style="margin-top: 0">
          {{ $address }}
      </p>
    </div>
  </div>
  <div class="main-body">
      <div class="date-and-subject">
              <em style="font-size: 8pt; margin-bottom: 0;">
                {{ $datePlaceLine }}
              </em>
          <p style="margin-top: 0;">
              {{ $subjectLine }}
          </p>
      </div>
      <div class="main-letter">
          {{ $slot }}
      </div>
      @if (!empty($additionalPages))
        <div class="additional-pages">
          {{ $additionalPages }}
        </div>
      @endif
  </div>
</div>