@props([
    'addressPosition' => 'left',
    'pp_postcode' => null,
    'pp_place' => null,
    'priorityMail' => false
])

@if (!in_array($addressPosition, ['left', 'right']))
    @php
        throw new \InvalidArgumentException("addressPosition must be 'left' or 'right'");
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
<div class="letter-content">
  <div class="workaround">.</div>
  <div class="address-block {{ $addressPosition === 'right' ? 'address-block-right' : 'address-block-left' }}">
    <div>
          <div style="position: relative; border-bottom: 1px solid black; margin-bottom: 3mm; font-family: Arial, Verdana, Helvetica, sans-serif; font-size: 8pt;">
                <div style="position: absolute; top: 0; right: 0; font-size: 6pt;">{{__('letter.post_company') }}</div>
              <b style="font-size:12pt;">
                P.P.
              </b>
              @if ($priorityMail)
                <b style="font-size:24pt; line-height: 0.5em;">
                  A
                </b>
              @endif
              {{ $pp_postcode }} {{ $pp_place }}
              <br>
              {{ $ppLine }}
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
