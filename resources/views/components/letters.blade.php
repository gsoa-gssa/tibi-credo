
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
            margin-top: 7cm;
        }

        .letter-content {
          position: relative;
        }

        .letter-content .address-block {
            width: 8cm;
            height: 2.5cm;
            position: absolute;
            /*display: flex;*/
            justify-content: center;
            padding-left: 1cm;
            padding-right: 1cm;
            box-sizing: border-box;
        }
        
        .workaround {
          color: white;
        }

        .letter-content .address-block-right {
          right: 0;
          top: 4.2cm;
        }

        .letter-content .address-block-left {
          top: 3.2cm;
        }

        .letter-content .date-and-subject {
            margin-top: 150px;
            max-width: 15cm
        }

        .letter-content .main-letter {
            margin-top: 20px;
            text-align: justify;
            max-width: 15cm
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
</head>
<body>
  {{ $slot }}
</body>