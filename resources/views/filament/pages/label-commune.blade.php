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
                    <li>{{ $zipcode->code }} {{ $zipcode->name }} ({{ $zipcode->number_of_dwellings }}/{{ $zipcode->getTotalDwellings() }})</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
