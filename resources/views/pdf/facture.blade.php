<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 50px; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        /* Header & Logo */
        .header-table { width: 100%; border: none; margin-bottom: 40px; }
        .logo { width: 150px; filter: grayscale(100%); }
        .company-info { text-align: right; font-size: 10px; color: #7f8c8d; }

        /* Titre et Infos Client */
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .info-section { width: 100%; margin-bottom: 30px; }
        .info-box { width: 50%; vertical-align: top; }
        .info-label { color: #7f8c8d; text-transform: uppercase; font-size: 9px; font-weight: bold; margin-bottom: 5px; }

        /* Tableau des produits */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items-table th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-transform: uppercase;
            font-size: 10px;
            border: none;
        }
        table.items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totaux */
        .totals-container { width: 100%; }
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 8px; border-bottom: 1px solid #ecf0f1; }
        .total-row { background: #f8f9fa; font-weight: bold; font-size: 13px; color: #2c3e50; }
        .total-row td { border-bottom: 2px solid #2c3e50; }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #bdc3c7;
            border-top: 1px solid #ecf0f1;
            padding-top: 10px;
        }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td>
            <img src="{{ public_path('images/logo.png') }}" class="logo">
        </td>
        <td class="company-info">
            <strong>FRPS - AD</strong><br>
            Sis: Interieur hospital regional de ngaoundéré<br>
            Contact : +237 699 087 986<br>
            Email : inf@vfrps-ad.cm
        </td>
    </tr>
</table>

<div class="invoice-title">Facture #{{ $commande->reference }}</div>

<table class="info-section">
    <tr>
        <td class="info-box">
            <div class="info-label">Facturé à :</div>
            <strong>{{ $commande->customer->name }}</strong><br>
            {{ $commande->adresse_livraison ?? $commande->customer->address }}<br>
            Tél : {{ $commande->customer->phone ?? 'N/A' }}
        </td>
        <td class="info-box text-right">
            <div class="info-label">Détails :</div>
            <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
            <strong>Statut :</strong> {{ $commande->string_status->value }}<br>
            <strong>Mode de règlement :</strong> Par virement / Espèces
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
    <tr>
        <th style="text-align: left;">Désignation</th>
        <th style="width: 10%;">Qté</th>
        <th style="width: 20%; text-align: right;">Prix Unitaire</th>
        <th style="width: 20%; text-align: right;">Montant Total</th>
    </tr>
    </thead>
    <tbody>
    @php $totalCalculé = 0; @endphp
    @foreach($commande->products as $article)
        @php
            $sousTotal = $article->quantite * $article->product->price;
            $totalCalculé += $sousTotal;
        @endphp
        <tr>
            <td>
                <strong>{{ $article->product->intitule }}</strong><br>
                <span style="color: #7f8c8d; font-size: 9px;">REF: {{ $article->product->referenceProduit }}</span>
            </td>
            <td class="text-center">{{ $article->quantite }}</td>
            <td class="text-right">{{ number_format($article->product->price, 0, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($sousTotal, 0, ',', ' ') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@php
    $tauxTVA = 0.1925;
    $appliquerTVA = false; // À lier à une logique métier si besoin
    $montantTVA = $appliquerTVA ? $totalCalculé * $tauxTVA : 0;
    $totalTTC = $totalCalculé + $montantTVA;
@endphp

<div class="totals-container">
    <table class="totals-table">
        <tr>
            <td>Total HT</td>
            <td class="text-right">{{ number_format($totalCalculé, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($appliquerTVA)
            <tr>
                <td>TVA (19,25%)</td>
                <td class="text-right">{{ number_format($montantTVA, 0, ',', ' ') }} FCFA</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL TTC</td>
            <td class="text-right">{{ number_format($totalTTC, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>
</div>

@if($commande->rest_to_pay > 0)
    <p style="text-align: right; color: #e74c3c;">
        <strong>Reste à payer : {{ number_format($commande->rest_to_pay, 0, ',', ' ') }} FCFA</strong>
    </p>
@endif

<div class="footer">
    <p>  MGNET - BP: 554 Ngaoundéré | Identifiant Unique: M121012429092J</p>
    <p><em>Merci de votre fidélité. Cette facture tient lieu de preuve d'achat.</em></p>
</div>

</body>
</html>
