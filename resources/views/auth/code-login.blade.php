<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Code Login</title>
    <link rel="stylesheet" href="/build/css/app.css" />
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-sm bg-white shadow p-6 rounded">
        <h1 class="text-xl font-semibold mb-4">Enter Login Code</h1>
        <form method="POST" action="{{ route('code-login.login') }}" class="space-y-4">
            @csrf
            <label class="block">
                <span class="text-sm text-gray-700">6-character code</span>
                <input name="code" maxlength="6" class="mt-1 block w-full border rounded px-3 py-2" placeholder="ABC123" value="{{ old('code') }}" />
            </label>
            @error('code')
            <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Login</button>
        </form>
        <p class="mt-4 text-xs text-gray-500">Codes are valid for 30 seconds and must be entered from the same IP as the admin who generated them.</p>
    </div>
</body>
</html>
