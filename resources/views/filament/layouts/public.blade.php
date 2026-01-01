<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @livewireStyles
    @filamentStyles
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="container mx-auto py-8">
        {{ $slot }}
    </main>
    @livewireScripts
@filamentScripts
</body>
</html>
