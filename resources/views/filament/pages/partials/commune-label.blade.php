<div class="content">
    <div class="name">{{ $commune->name_with_canton }}</div>
    @if ($commune->zipcodes->count() > 0)
    <div class="zipcodes">
        <div class="zipcodes-title">Postcodes:</div>
        <ul class="zipcodes-list">
            @foreach ($commune->zipcodes as $zipcode)
            <li>
                <div>{{ $zipcode->code }} {{ $zipcode->name }} ({{ $zipcode->number_of_dwellings }}/{{ $zipcode->getTotalDwellings() }})</div>
                @php $streets = $zipcode->getStreetsWithNumbersSummary(); @endphp
                @if (!empty($streets['same_commune']) || !empty($streets['other_communes']))
                <div class="streets-container">
                    <div class="streets-column">
                        <div class="streets-column-title">{{ $commune->name }}</div>
                        @if (!empty($streets['same_commune']))
                            {{ $streets['same_commune'] }}
                        @else
                            <em>None</em>
                        @endif
                    </div>
                    <div class="streets-column">
                        <div class="streets-column-title">Other communes</div>
                        @if (!empty($streets['other_communes']))
                            {{ $streets['other_communes'] }}
                        @else
                            <em>None</em>
                        @endif
                    </div>
                </div>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
