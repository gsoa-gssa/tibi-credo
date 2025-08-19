<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts Export - {{ $exportDate->format('Y-m-d') }}</title>
    <style>
        @page {
            margin: 2cm;
            size: A4;
        }
        .contact-page {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @foreach($pages as $page)
        {!! $page !!}
    @endforeach
</body>
</html>