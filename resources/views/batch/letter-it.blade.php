<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.25;
            position: relative;
        }
        #main-body {
            margin-left: 1.25cm;
            margin-right: 1.25cm;
            margin-top: 10cm;
        }

        #address-block {
            width: 8cm;
            height: 2.5cm;
            position: absolute;
            top: 4.5cm;
            left: 1.25cm;
            display: flex;
            justify-content: center;
            padding-left: 1cm;
            padding-right: 1cm;
            box-sizing: border-box;
        }

        #date-and-subject {
            margin-top: 150px;
            max-width: 15cm
        }

        #main-letter {
            margin-top: 20px;
            text-align: justify;
            max-width: 15cm
        }

        #thank-you {
            margin-top: 50px;
            max-width: 15cm
        }

        #sheets-table {
            page-break-before: always;
            width: 100%;
            margin-top: 1.2cm;
        }

        table {
            width: fit-content;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid black;
        }

        th, td {
            border: 1px solid black;
            padding: 5px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }

        .monospace {
            font-family: monospace;
            letter-spacing: 0.1em;
        }
    </style>
</head>
<body>
    <div id="address-block">
        <div>
            <p style="border-bottom: 1px solid black; margin-bottom: 0">
                <small>
                    Alleanza per la proibizione delle armi nucleari,<br>
                    CP 1069, 8031 Zurigo</br>
                </small>
            </p>
            <p style="margin-top: 0">
                {!! $batch->commune->address !!}
            </p>
        </div>
    </div>
    <div id="main-body">

        <div id="date-and-subject">
            <p>
                <em style="font-size: 8pt">Zurigo, {{$batch->created_at->format("d.m.Y")}}</em>
            </p>
            <p>
                <b>Soggetto :</b> Attestazione del diritto di voto «Iniziativa per la proibizione delle armi nucleari»
            </p>
        </div>
        <div id="main-letter">
            <p>
                <b>Gentili Signore e Signori,</b>
            </p>
            <p>
                Visti gli articoli 62, 63 e 70 della legge federale del 17 dicembre 1976 sui diritti politici vi inviamo in allegato {{count($batch->sheets)}} liste delle firme a sostegno della nostra Iniziativa popolare federale «Per l’adesione della Svizzera al Trattato delle Nazioni Unite sulla proibizione delle armi nucleari (Iniziativa per la proibizione delle armi nucleari)» sulle quali figurano complessivamente {{$batch->sheets->sum("signatureCount")}} firme. I numeri di riferimento delle schede sono riportati nella tabella della/e pagina/e seguente/i.
            </p>
            <p>
                Vi preghiamo cortesemente di attestare il diritto di voto dei firmatari e di rinviarci le liste con le relative attestazioni entro due settimane al seguente indirizzo:
            </p>
            <p>
                <b>Alleanza per la proibizione delle armi nucleari</b></br>
                CP 1069, 8031 Zürich</br>
            </p>
            <p>
                Per qualsiasi domanda, non esitate a contattarci al numero +41 79 426 94 48 o via e-mail all'indirizzo <a href=“mailto:noemi@divieto-armi-nucleari.ch”>noemi@divieto-armi-nucleari.ch</a>.
            </p>
        </div>
        <div id="thank-you">
            <p>
                Vi ringraziamo per il vostro sostegno e vi inviamo i nostri migliori saluti.</br>
                <b>Alleanza per la proibizione delle armi nucleari</b><br><br>
                <small><em>Il presente documento è valido senza firma.</em></small><br>
                <small><b>Appendice:</b> Tabella dei numeri di riferimento dei fogli firma</small>
            </p>
        </div>

        <div id="sheets-table">
            <table>
                <thead>
                    <tr>
                        <th>Numeri di riferimento</th>
                        <th>Numero di firme</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batch->sheets as $sheet)
                        <tr>
                            <td class="monospace">{{$sheet->label}}</td>
                            <td style="text-align: end">{{$sheet->signatureCount}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
