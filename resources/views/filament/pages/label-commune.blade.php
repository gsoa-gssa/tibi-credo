<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $record->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: serif;
            padding: 1cm;
        }
        .label {
            border: none;
            padding: 0;
            width: 100%;
            min-height: 5cm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .content {
            text-align: center;
        }
        .name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 0.5cm;
        }
        .canton {
            font-size: 16px;
            color: #666;
        }
        .zipcodes {
            margin-top: 1cm;
            text-align: left;
        }
        .zipcodes-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 0.3cm;
        }
        .zipcodes-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5cm;
        }
        .zipcodes-list {
            font-size: 11px;
            list-style: none;
            line-height: 1.4;
            column-count: 2;
            column-gap: 0.5cm;
        }
        .zipcodes-list li {
            break-inside: avoid-column;
            page-break-inside: avoid;
        }
        .zipcodes-list li:nth-child(even) {
            background: #f5f5f5;
        }
        .street-numbers {
            font-size: 10px;
            padding-left: 0.5cm;
            color: #555;
            margin-top: 0.2cm;
        }
        .streets-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.3cm;
            margin-top: 0.1cm;
        }
        .streets-column {
            font-size: 10px;
            padding: 0.1cm 0;
        }
        .streets-column-title {
            font-weight: bold;
            margin-bottom: 0.2cm;
            font-size: 10px;
        }
        @media print {
            body {
                margin: 0;
                padding: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="content">
            <div class="name">{{ $record->name_with_canton }}</div>
            @if ($record->zipcodes->count() > 0)
            <div class="zipcodes">
                <ul class="zipcodes-list">
                    @foreach ($record->zipcodes as $zipcode)
                    <li>
                        <div>{{ $zipcode->code }} {{ $zipcode->name }} ({{ $zipcode->number_of_dwellings }}/{{ $zipcode->getTotalDwellings() }})</div>
                        @php $streets = $zipcode->getStreetsWithNumbersSummary(); @endphp
                        @if (!empty($streets['same_commune']) || !empty($streets['other_communes']))
                        <div class="streets-container">
                            <div class="streets-column">
                                <div class="streets-column-title">{{ __('commune.label.addresses_in_this_commune', ['name' => $record->name]) }}</div>
                                @if (!empty($streets['same_commune']))
                                    {{ $streets['same_commune'] }}
                                @else
                                    <em>None</em>
                                @endif
                            </div>
                            <div class="streets-column">
                                <div class="streets-column-title">{{ __('commune.label.addresses_in_other_communes') }}</div>
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
    </div>
</body>
</html>
