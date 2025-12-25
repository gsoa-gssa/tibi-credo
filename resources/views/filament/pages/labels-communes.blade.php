<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commune Labels</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: serif; padding: 1cm; }
        .page {
            width: 210mm; min-height: 297mm; padding: 0; display: block;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }
        .content { text-align: center; width: 100%; }
        .name { font-size: 24px; font-weight: bold; margin-bottom: 0.5cm; }
        .zipcodes { margin-top: 1cm; text-align: left; }
        .zipcodes-title { font-size: 12px; font-weight: bold; margin-bottom: 0.3cm; }
        .zipcodes-list { font-size: 11px; list-style: none; line-height: 1.4; column-count: 2; column-gap: 0.5cm; }
        .zipcodes-list li { break-inside: avoid-column; page-break-inside: avoid; }
        .zipcodes-list li:nth-child(even) { background: #f5f5f5; }
        .streets-container { display: grid; grid-template-columns: 1fr 1fr; gap: 0.3cm; margin-top: 0.1cm; }
        .streets-column { font-size: 10px; padding: 0.1cm 0; }
        .streets-column-title { font-weight: bold; margin-bottom: 0.2cm; font-size: 10px; }
        @media print { body { margin: 0; padding: 1cm; } }
    </style>
</head>
<body>
    @foreach ($communes as $commune)
        <div class="page">
            @include('filament.pages.partials.commune-label', ['commune' => $commune])
        </div>
    @endforeach
</body>
</html>
