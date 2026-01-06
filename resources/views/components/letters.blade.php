
<!DOCTYPE html>
<html>
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

        .letter-content .main-body {
            margin-top: 11cm;
        }

        .letter-content {
          position: relative;
        }

        .letter-content .address-block {
            width: 8cm;
            height: 2.5cm;
            position: absolute;
            top: 5.2cm;
            /*display: flex;*/
            font-family: Arial, Helvetica, sans-serif;
            font-size: 3mm;
            justify-content: center;
            padding-left: 1cm;
            padding-right: 1cm;
            box-sizing: border-box;
        }
        
        .workaround {
          color: white;
        }

        .letter-content .address-block-right {
          right: 0.9cm;
        }

        .letter-content .address-block-left {
          left: 0.9cm;
        }

        .letter-content .date-and-subject {
            margin-top: 150px;
        }

        .letter-content .main-letter {
            margin-top: 20px;
            text-align: justify;
        }

        .monospace {
            font-family: monospace;
            letter-spacing: 0.1em;
        }

        .letter-content .additional-pages {
          page-break-before: always;
          width: 100%;
        }

        .letter-content {
          page-break-after: always;
        }

        .letter-content:last-child {
            page-break-after: auto;
        }

        @if(isset($css))
          {{ $css }}
        @endif
    </style>
    @if (file_exists(public_path('build/manifest.json')))
      @vite('resources/js/bwip-datamatrix.js')
      <script>
        console.log("Loaded bwip-datamatrix.js via Vite");
      </script>
    @endif
</head>
<body>
  {{ $slot }}
</body>