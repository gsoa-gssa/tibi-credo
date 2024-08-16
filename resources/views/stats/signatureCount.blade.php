<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Signature Count</title>
    @vite("resources/css/app.scss")
</head>
<body>

    <div class="stats-app min-h-screen bg-black flex justify-center items-center">
        <div class="stats-app__container px-4 py-12 w-full max-w-[793px]">
            <div class="stats-app__total bg-accent font-mono p-4 text-center">
                <p class="italic">Totale anzahl Unterschriften</p>
                <p class="font-black text-8xl" id="count-total">{{ str_pad($count["total"], 6, "0", STR_PAD_LEFT) }}</p>
            </div>
            <div class="stats-app__details flex gap-x-8 mt-8">
                <div class="stats-app__details__item flex-1 bg-accent font-mono p-4 text-center">
                    <p class="italic">Heute</p>
                    <p class="font-black" id="count-24h">{{ str_pad($count["today"], 4, "0", STR_PAD_LEFT) }}</p>
                </div>
                <div class="stats-app__details__item flex-1 bg-accent font-mono p-4 text-center">
                    <p class="italic">30 Minuten</p>
                    <p class="font-black" id="count-30m">{{ str_pad($count["thirtyMinutes"], 4, "0", STR_PAD_LEFT) }}</p>
                </div>
            </div>
        </div>
    </div>
    @vite(["resources/js/app.js", "resources/js/stats/signatureCount.js"])
</body>
</html>
