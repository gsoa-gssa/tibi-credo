@props(['addressPosition' => 'left'])

@if (!in_array($addressPosition, ['left', 'right']))
    @php
        throw new \InvalidArgumentException("addressPosition must be 'left' or 'right'");
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
          <p style="border-bottom: 1px solid black; margin-bottom: 3mm">
              <small>
                {{ $ppLine }}
              </small>
          </p>
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
