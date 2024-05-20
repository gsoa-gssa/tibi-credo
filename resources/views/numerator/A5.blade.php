<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>

<style>
    @page { margin: 0px; }
    body { margin: 0px; }

    .page {
        width: 210mm;
        height: 148mm;
        margin: 0;
        padding: 0;
        position: relative;
    }

    .page+.page {
        page-break-before: always;
    }

    .page .numerator-id {
        position: absolute;
        top: 6.62mm;
        left: 165.989mm;
        width: 35.414mm;
        height: 10.264mm;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .page .numerator-id span {
        font-size: 12pt;
        font-weight: bold;
        font-family: monospace;
    }
</style>

@foreach ($numerators as $numerator)


<div class="page">
    <div class="numerator-id">
        <span>{{
            // Add padding zeros, split into 2 parts, and join with a space
            implode(' ', str_split(str_pad($numerator->id, 6, '0', STR_PAD_LEFT), 3))
        }}</span>
    </div>
</div>

@endforeach

</body>
</html>
