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
            top: 4.3cm;
            right: 0;
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
                    Alliance pour l’interdiction des armes nucléaires,<br>
                    CP 1069, 8031 Zürich</br>
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
                <b>Sujet :</b> Attestation de la qualité d’électeur pour l'initiative pour l’interdiction des armes nucléaires
            </p>
        </div>
        <div id="main-letter">
            <p>
                <b>Madame, Monsieur,</b>
            </p>
            <p>
                Nous fondant sur les articles 62, 63 et 70 de la loi fédérale du 17 décembre 1976 sur les droits politiques, nous vous remettons ci-joint {{count($batch->sheets)}} listes de signatures à l’appui de notre Initiative populaire fédérale «Pour l’adhésion de la Suisse au Traité des Nations Unies sur l’interdiction des armes nucléaires (initiative pour l’interdiction des armes nucléaires)» comprenant au total {{$batch->sheets->sum("signatureCount")}} signatures. Les numéros de référence des feuilles sont indiqués dans le tableau de la ou des pages suivantes.
            </p>
            <p>
                Nous vous prions de bien vouloir attester le droit de vote des signataires et renvoyer, dans un délai de deux semaines au maximum, les listes de signatures validées à l’adresse suivante:
            </p>
            <p>
                <b>Alliance pour l’interdiction des armes nucléaires</b></br>
                CP 1069, 8031 Zürich</br>
            </p>
            <p>
                Si vous avez des questions, n'hésitez pas à nous contacter au +41 79 441 80 05 ou par e-mail à <a href="mailto:lukas@interdiction-armes-nucleaires.ch">lukas@interdiction-armes-nucleaires.ch</a>.
            </p>
        </div>
        <div id="thank-you">
            <p>
                Nous vous remercions de votre soutien et vous prions d'agréer, Madame, Monsieur, nos salutations les meilleures.</br>
                <b>Alliance pour l’interdiction des armes nucléaires</b><br><br>
                <small><em>Ce document est valable sans signature.</em></small><br>
                <small><b>Annexe :</b> Tableau des numéros de référence des feuilles de signatures</small>
            </p>
        </div>

        <div id="sheets-table">
            <table>
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Nombre de signatures</th>
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
