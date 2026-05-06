<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 120px;
            float: left;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }
        .clearfix {
            clear: both;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .signature {
            margin-top: 50px;
        }
        .signature div {
            width: 45%;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/logo.png') }}">
    <div class="title">BORDEREAU DE LIVRAISON</div>
    <div class="clearfix"></div>
</div>

<p><strong>Commande n° :</strong> {{ $commande->id }}</p>
<p><strong>Client :</strong> {{ $commande->customer->name }}</p>
<p><strong>Adresse de livraison :</strong> {{ $commande->adresse_livraison }}</p>
<p><strong>Date de livraison :</strong> {{ now()->format('d/m/Y') }}</p>

<table>
    <thead>
    <tr>
        <th style="width:55%">Produit</th>
        <th style="width:15%">Quantité</th>
        <th style="width:30%">Observation</th>
    </tr>
    </thead>
    <tbody>
    @foreach($commande->products as $article)
        <tr>
            <td>{{ $article->product->intitule }}</td>
            <td style="text-align:center">{{ $article->quantite }}</td>
            <td></td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="signature">
    <div>
        <p><strong>Signature du livreur</strong></p>
        <p>______________________________</p>
    </div>

    <div style="float:right">
        <p><strong>Signature du client</strong></p>
        <p>______________________________</p>
    </div>
</div>

</body>
</html>
