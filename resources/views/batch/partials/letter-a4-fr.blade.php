@php
  $sheet_labels = $batch->sheetsHTMLString();
@endphp
<x-letters>
  <x-letter addressPosition="{{ $addressPosition }}" pp_postcode="8031" pp_place="Zurich" priorityMail="{{ $priorityMail ?? false }}">
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
      Zurich, {{$batch->created_at->format("d.m.Y")}}
    </x-slot>
    <x-slot name="subjectLine">
      <b>Sujet :</b> Attestation de la qualité d’électeur pour l'initiative pour l’interdiction des armes nucléaires
    </x-slot>
    <p style="border: 5px solid black; padding: 5px;">
      <b style="font-size: 2rem; ">Dépôt prévu le 23 décembre</b>
      <br>
      @if($batch->created_at->lt(\Carbon\Carbon::create(2025, 12, 19)))
        S'il n'est pas possible de renvoyer les listes certifiées avant le 19 décembre 2025 (courrier A, livraison chez nous le samedi 20 décembre 2025), veuillez nous contacter.
      @endif
    </p>
    <br>
    <p>
      Madame, Monsieur,
    </p>
    <p>
      Nous fondant sur les articles 62, 63 et 70 de la loi fédérale du 17 décembre 1976 sur les droits politiques, nous vous remettons ci-joint {{count($batch->sheets)}} liste(s) de signatures à l’appui de notre Initiative populaire fédérale «Pour l’adhésion de la Suisse au Traité des Nations Unies sur l’interdiction des armes nucléaires (initiative pour l’interdiction des armes nucléaires)» comprenant au total {{$batch->sheets->sum("signatureCount")}} signature(s).
      Les numéros de référence des feuilles sont indiqués dans le tableau de la ou des pages suivantes.
      @if($sheet_labels != '')
        Les feuilles ont les numéros de référence suivants : {!! $sheet_labels !!}.
      @endif
    </p>
    <p>
      Nous vous prions de bien vouloir attester le droit de vote des signataires et renvoyer aussi vite que possible, les listes de signatures validées à l’adresse suivante:
    </p>
    <p>
      <b>Alliance pour l’interdiction des armes nucléaires</b></br>
      CP 1069, 8031 Zurich</br>
    </p>
    <p>
      Si vous avez des questions, n'hésitez pas à nous contacter au +41 79 441 80 05 ou par e-mail à <a href="mailto:lukas@interdiction-armes-nucleaires.ch">lukas@interdiction-armes-nucleaires.ch</a>.
    </p>
    <div id="thank-you">
      <p>
        Nous vous remercions de votre soutien et vous prions d'agréer, Madame, Monsieur, nos salutations les meilleures.</br>
        <b>Alliance pour l’interdiction des armes nucléaires</b><br><br>
        <small><em>Ce document est valable sans signature.</em></small><br>
      </p>
    </div>
  </x-letter>
</x-letters>