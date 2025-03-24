<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $news->title }}</title>
    <style>
        /* Estilos inline para compatibilidade */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #777;
        }
        .content {
            padding: 20px 0;
            font-size: 16px;
            line-height: 1.5;
            text-align: justify;
        }
        .news-image_url {
            width: 100%;
            height: auto;
            display: block;
            margin: 15px 0;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .footer img {
            width: 120px;
            height: auto;
            margin-top: 10px;
        }
        a {
            color: #3490dc;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $news->title }}</h1>
            <p>Data da publicação {{ $news->created_at->format('d/m/Y') }}</p>
        </div>

        <div class="content">
            @if(isset($news->image_url) && $news->image_url)
                <img src="{{ $news->image_url }}" alt="Imagem da notícia" class="news-image_url">
            @endif

            <p>{{ $news->description }}</p>

            <p>
                <a href="{{ $news->link }}" target="_blank">Leia a notícia completa</a>
            </p>
        </div>

        <div class="footer">
            <p>Enviado por <strong>E-Inov</strong></p>
            <img src="{{ asset('storage/einov.png') }}" alt="E-Inov">
        </div>
    </div>
</body>
</html>
