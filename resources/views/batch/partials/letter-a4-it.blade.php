<x-letters>
  <x-letter pp_postcode="8031" pp_place="Zurigo" addressPosition="{{ $addressPosition }}" priorityMail="{{ $priorityMail ?? false }}">
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
      Zurigo, {{$batch->created_at->format("d.m.Y")}}
    </x-slot>
    <x-slot name="subjectLine">
      <b>Soggetto :</b> Attestazione del diritto di voto «Iniziativa per la proibizione delle armi nucleari»
    </x-slot>
    <b style="font-size: 2rem; border: 5px solid black; padding: 5px;">Consegna prevista il 23 dicembre</b>
    <p>
        <b>Gentili Signore e Signori,</b>
    </p>
    <p>
        Visti gli articoli 62, 63 e 70 della legge federale del 17 dicembre 1976 sui diritti politici vi inviamo in allegato {{count($batch->sheets)}} liste delle firme a sostegno della nostra Iniziativa popolare federale «Per l’adesione della Svizzera al Trattato delle Nazioni Unite sulla proibizione delle armi nucleari (Iniziativa per la proibizione delle armi nucleari)» sulle quali figurano complessivamente {{$batch->sheets->sum("signatureCount")}} firme. I numeri di riferimento delle schede sono riportati nella tabella della/e pagina/e seguente/i.
    </p>
    <p>
        Vi preghiamo cortesemente di attestare il diritto di voto dei firmatari e di rinviarci le liste con le relative attestazioni il prima possibile al seguente indirizzo:
    </p>
    <p>
        <b>Alleanza per la proibizione delle armi nucleari</b></br>
        CP 1069, 8031 Zürich</br>
    </p>
    <p>
        Per qualsiasi domanda, non esitate a contattarci al numero +41 79 426 94 48 o via e-mail all'indirizzo <a href=“mailto:noemi@divieto-armi-nucleari.ch”>noemi@divieto-armi-nucleari.ch</a>.
    </p>
    <div id="thank-you">
      <p>
        Vi ringraziamo per il vostro sostegno e vi inviamo i nostri migliori saluti.</br>
        <b>Alleanza per la proibizione delle armi nucleari</b><br><br>
        <small><em>Il presente documento è valido senza firma.</em></small><br>
        <small><b>Appendice:</b> Tabella dei numeri di riferimento dei fogli firma</small>
      </p>
    </div>
    <x-slot name="additionalPages">
      <div id="sheets-table">
        <table>
          <thead>
            <tr>
              <th>Numeri di riferimento</th>
              <th>Numero di firme</th>
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