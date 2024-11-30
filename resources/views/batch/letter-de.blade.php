<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.25;
            position: relative;
        }
        #main-body {
            margin-top: 10cm;
        }

        #address-block {
            width: 8cm;
            height: 2.5cm;
            position: absolute;
            top: 3.2cm;
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
                    Allianz für ein Atomwaffenverbot,<br>
                    PF 1069, 8031 Zürich</br>
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
                <em style="font-size: 8pt">Zürich, {{$batch->created_at->format("d.m.Y")}}</em>
            </p>
            <p>
                <b>Betreff:</b> Stimmrechtsbescheinigung der Unterschriftenbögen für die Atomwaffenverbots-Initiative
            </p>
        </div>
        <div id="main-letter">
            <p>
                <b>Sehr geehrte Damen und Herren,</b>
            </p>
            <p>
                Geschützt auf die Artikel 62, 63 und 70 Bundesgesetz über die politische Rechte vom 17. Dezember 1976 stellen wir Ihnen in der Beilage {{count($batch->sheets)}} Unterschriftenliste für die eidgenössische Volksinitiative für den Beitritt der Schweiz zum UNO-Atomwaffenverbotsvertrag «Atomwaffenverbots-Initiative» mit insgesamt {{$batch->sheets->sum("signatureCount")}} Unterschriften zu. Die Referenznummern der Bögen können Sie der Tabelle der folgenden Seite(n) entnehmen.
            </p>
            <p>
                Wir ersuchen Sie höflich, das Stimmrecht der Unterzeichnerinnen und Unterzeichner zu bescheinigen. Bitte achten Sie darauf, dass die Felder für Ort, Datum, eigenhändige Unterschrift, amtliche Eigenschaft und Amtsstempel auf allen Unterschriftenlisten vollständig ausgefüllt sind. Dürfen wir Sie bitten, die Unterschriftenlisten innerhalb einer Woche bescheinigt zurückzusenden an:
            </p>
            <p>
                <b>Allianz für ein Atomwaffenverbot</b></br>
                Postfach 1069, 8031 Zürich</br>
            </p>
            <p>
                Sollten Sie Fragen haben, zögern Sie bitte nicht, uns unter der Nummer +41 79 441 80 05 oder per E-Mail unter <a href="mailto:lukas@atomwaffenverbot.ch">lukas@atomwaffenverbot.ch</a> zu kontaktieren.
            </p>
        </div>
        <div id="thank-you">
            <p>
                Wir danken Ihnen für Ihre Unterstützung und verbleiben mit freundlichen Grüssen</br>
                <b>Allianz für ein Atomwaffenverbot</b><br><br>
                <small><em>Dieses Dokument ist gültig ohne Unterschrift.</em></small><br>
                <small><b>Anhang:</b> Tabelle mit Referenznummern der Unterschriftenbögen</small>
            </p>
        </div>

        <div id="sheets-table">
            <table>
                <thead>
                    <tr>
                        <th>Nummer</th>
                        <th>Anzahl Unterschriften</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batch->sheets->sortBy("label") as $sheet)
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
