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
            margin-bottom: 20px;
        }
        .header img {
            width: 120px;
        }
        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }
        .info p {
            margin: 4px 0;
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
            background: #f2f2f2;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 15px;
            width: 100%;
        }
        .totals td {
            border: 1px solid #333;
            padding: 8px;
        }
        .totals .label {
            width: 75%;
        }
        .totals .value {
            width: 25%;
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/logo.png') }}">
</div>

<div class="title">FACTURE DÉFINITIVE</div>

<div class="info">
    <p><strong>Commande n° :</strong> {{ $commande->id }}</p>
    <p><strong>Client :</strong> {{ $commande->client->nom }}</p>
    <p><strong>Date :</strong> {{ now()->format('d/m/Y') }}</p>
</div>

<table>
    <thead>
    <tr>
        <th>Produit</th>
        <th style="width: 15%">Quantité</th>
        <th style="width: 20%">Prix unitaire (FCFA)</th>
        <th style="width: 20%">Total (FCFA)</th>
    </tr>
    </thead>
    <tbody>
    @php $totalHT = 0; @endphp
    @foreach($commande->articles as $article)
        @php
            $prix = $article->produit->prix;
            $quantite = $article->quantite;
            $total = $prix * $quantite;
            $totalHT += $total;
        @endphp
        <tr>
            <td>{{ $article->produit->nom }}</td>
            <td class="text-right">{{ $quantite }}</td>
            <td class="text-right">{{ number_format($prix, 0, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($total, 0, ',', ' ') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@php
    $tauxTVA = 0.1925;
    $montantTVA = $avecTVA ? $totalHT * $tauxTVA : 0;
    $totalTTC = $totalHT + $montantTVA;
@endphp

<table class="totals">
    <tr>
        <td class="label">Total HT</td>
        <td class="value">{{ number_format($totalHT, 0, ',', ' ') }} FCFA</td>
    </tr>

    @if($avecTVA)
        <tr>
            <td class="label">TVA (19,25%)</td>
            <td class="value">{{ number_format($montantTVA, 0, ',', ' ') }} FCFA</td>
        </tr>
    @endif

    <tr>
        <td class="label">Total TTC</td>
        <td class="value">{{ number_format($totalTTC, 0, ',', ' ') }} FCFA</td>
    </tr>
</table>

<div class="footer">
    <p><em>Cette facture est payable à réception. Merci de votre confiance.</em></p>
    <br>
    <p>Signature : ________________________________</p>
</div>

</body>
</html>
