<x-letters>
  <x-letter addressPosition="{{ $addressPosition }}" pp_postcode="8031" pp_place="Zürich" priorityMail="{{ $priorityMail ?? false }}">
    <x-slot name="css">
      #thank-you {
          margin-top: 50px;
          max-width: 15cm
      }

      #sheets-table {
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
    </x-slot>
    <x-slot name="ppLine">
      {{__('letter.pp_sender') }}
    </x-slot>
    <x-slot name="address">
      {!! $batch->commune->address !!}
    </x-slot>
    <x-slot name="datePlaceLine">
      Zürich, {{$batch->created_at->format("d.m.Y")}}
    </x-slot>
    <x-slot name="subjectLine">
      <b>Betreff:</b>
      Stimmrechtsbescheinigung der Unterschriftenbögen für die Atomwaffenverbots-Initiative
    </x-slot>
    <p>
      <b>Sehr geehrte Damen und Herren,</b>
    </p>
    <p>
      Geschützt auf die Artikel 62, 63 und 70 Bundesgesetz über die politische Rechte vom 17. Dezember 1976 stellen wir Ihnen in der Beilage {{count($batch->sheets)}} Unterschriftenliste(n) für die eidgenössische Volksinitiative für den Beitritt der Schweiz zum UNO-Atomwaffenverbotsvertrag «Atomwaffenverbots-Initiative» mit insgesamt {{$batch->sheets->sum("signatureCount")}} Unterschriften zu. Die Referenznummern der Bögen können Sie der Tabelle der folgenden Seite(n) entnehmen.
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
    <div id="thank-you">
      <p>
        Wir danken Ihnen für Ihre Unterstützung und verbleiben mit freundlichen Grüssen</br>
        <b>Allianz für ein Atomwaffenverbot</b><br><br>
        <small><em>Dieses Dokument ist gültig ohne Unterschrift.</em></small><br>
        <small><b>Anhang:</b> Tabelle mit Referenznummern der Unterschriftenbögen</small>
      </p>
    </div>
    <x-slot name="additionalPages">
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
    </x-slot>
  </x-letter>
</x-letters>