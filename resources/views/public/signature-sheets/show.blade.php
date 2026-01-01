<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sheet->short_name }}</title>
</head>
<body>
    <h1>{{ $sheet->short_name }}</h1>
    <h2>{{ __('source.namePlural') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('source.fields.code') }}</th>
                <!-- <th>{{ __('source.fields.short_description') }}</th> -->
                <th>{{ __('source.actions.download_pdf') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($sheet->sources as $source)
            <tr>
                <td>{{ $source->code }}</td>
                <!-- <td>{{ $source->getLocalized('short_description') }}</td> -->
                <td>
                    <a href="{{ URL::temporarySignedRoute('public.signature-sheets.download', now()->addMinutes(30), ['sheet' => $sheet->id, 'source' => $source->id, 'signature_collection_id' => $scopeId]) }}">
                        {{ __('source.actions.download_pdf') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
