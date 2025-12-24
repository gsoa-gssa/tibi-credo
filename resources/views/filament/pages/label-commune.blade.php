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
            padding: 2cm;
        }
        .label {
            border: 1px solid #333;
            padding: 1.5cm;
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
        .zipcodes-list {
            font-size: 11px;
            list-style: none;
            line-height: 1.4;
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
            gap: 0.5cm;
            margin-top: 0.3cm;
            padding-left: 0.5cm;
        }
        .streets-column {
            font-size: 10px;
            padding: 0.3cm;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .streets-column-title {
            font-weight: bold;
            margin-bottom: 0.2cm;
            font-size: 10px;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
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
                <div class="zipcodes-title">Postcodes:</div>
                <ul class="zipcodes-list">
                    @foreach ($record->zipcodes as $zipcode)
                    <li>
                        <div>{{ $zipcode->code }} {{ $zipcode->name }} ({{ $zipcode->number_of_dwellings }}/{{ $zipcode->getTotalDwellings() }})</div>
                        @php $streets = $zipcode->getStreetsWithNumbers(); @endphp
                        @if (!empty($streets['same_commune']) || !empty($streets['other_communes']))
                        <div class="streets-container">
                            <div class="streets-column">
                                <div class="streets-column-title">{{ $record->name }}</div>
                                @if (!empty($streets['same_commune']))
                                    {{ implode(', ', $streets['same_commune']) }}
                                @else
                                    <em>None</em>
                                @endif
                            </div>
                            <div class="streets-column">
                                <div class="streets-column-title">Other communes</div>
                                @if (!empty($streets['other_communes']))
                                    {{ implode(', ', $streets['other_communes']) }}
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
