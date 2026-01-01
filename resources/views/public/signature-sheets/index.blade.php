<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('signatureSheet.namePlural') }}</title>
</head>
<body>
    <h1>{{ __('signatureSheet.namePlural') }}</h1>
    <table>
        <thead>
            <tr>
                <th>{{ __('signatureSheet.fields.short_name') }}</th>
                <th>{{ __('View') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($sheets as $sheet)
            <tr>
                <td>{{ $sheet->short_name }}</td>
                <td>
                    <a href="{{ URL::temporarySignedRoute('public.signature-sheets.show', now()->addMinutes(30), ['sheet' => $sheet->id, 'signature_collection_id' => $scopeId]) }}">
                        {{ __('View') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
