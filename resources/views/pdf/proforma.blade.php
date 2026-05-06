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
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
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
        }
        .text-right {
            text-align: right;
        }
        .total {
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/logo.png') }}">
    <div class="title">FACTURE PRO FORMA</div>
</div>

<p><strong>Commande n° :</strong> {{ $commande->id }}</p>
<p><strong>Client :</strong> {{ $commande->customer->name }}</p>
<p><strong>Date :</strong> {{ now()->format('d/m/Y') }}</p>

<table>
    <thead>
    <tr>
        <th>Produit</th>
        <th>Qté</th>
        <th>PU (FCFA)</th>
        <th>Total (FCFA)</th>
    </tr>
    </thead>
    <tbody>
    @php $total = 0; @endphp
    @foreach($commande->products as $article)
        @php
            $sousTotal = $article->quantite * $article->product->price;
            $total += $sousTotal;
        @endphp
        <tr>
            <td>{{ $article->product->intitule }}</td>
            <td class="text-right">{{ $article->quantite }}</td>
            <td class="text-right">{{ number_format($article->product->price, 0, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($sousTotal, 0, ',', ' ') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total">
        <td colspan="3">TOTAL</td>
        <td class="text-right">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
    </tr>
    </tfoot>
</table>

</body>
</html>
