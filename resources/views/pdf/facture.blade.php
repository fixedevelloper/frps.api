<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ $commande->reference }}</title>
    <style>
        @page { margin: 40px 50px 60px 50px; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        /* En-tête & Logo */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .logo { max-width: 160px; height: auto; }
        .company-info {
            text-align: right;
            font-size: 10px;
            color: #7f8c8d;
            line-height: 1.4;
        }

        /* Titre Document */
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 5px;
            letter-spacing: 0.5px;
        }

        /* Blocs d'informations */
        .info-section {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .info-box {
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Structure du Tableau Principal */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.main-table th {
            background: #2c3e50;
            color: white;
            padding: 10px 8px;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: bold;
        }
        table.main-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }

        /* Alignements & Utilitaires */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        /* Section des totaux */
        .totals-container {
            width: 100%;
            margin-top: 20px;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 11px;
        }
        .total-row {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 13px;
            color: #2c3e50;
        }
        .total-row td {
            border-top: 1px solid #2c3e50;
            border-bottom: 2px solid #2c3e50;
        }

        .rest-pay {
            text-align: right;
            color: #c0392b;
            margin-top: 15px;
            font-size: 12px;
        }

        /* Pied de page sécurisé pour PDF */
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 9px;
            color: #95a5a6;
            border-top: 1px solid #ecf0f1;
            padding-top: 8px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

<!-- En-tête de la facture -->
<table class="header-table">
    <tr>
        <td class="text-left">
            <img src="{{ public_path('images/logo.png') }}" class="logo">
        </td>
        <td class="company-info">
            <strong style="color: #2c3e50; font-size: 12px;">FRPS - AD</strong><br>
            Sis: Intérieur Hôpital Régional de Ngaoundéré<br>
            Contact : +237 699 087 986<br>
            Email : info@frps-ad.cm
        </td>
    </tr>
</table>

<div class="invoice-title">BL/Facture #{{ $commande->reference }}</div>

<!-- Section de facturation & métadonnées -->
<table class="info-section">
    <tr>
        <td class="info-box">
            <div class="info-label">Facturé à :</div>
            <strong style="font-size: 13px; color: #2c3e50;">{{ $commande->customer->name }}</strong><br>
            <span style="color: #555;">
                {{ $commande->adresse_livraison ?? $commande->customer->address }}<br>
                Tél : {{ $commande->customer->phone ?? 'N/A' }}
            </span>
        </td>
        <td class="info-box text-right">
            <div class="info-label">Détails du document :</div>
            <strong>Date d'émission :</strong> {{ now()->format('d/m/Y') }}<br>
            <strong>Statut :</strong> {{ $commande->string_status->value ?? 'N/A' }}<br>
            <strong>Mode de règlement :</strong> Par virement / Espèces
        </td>
    </tr>
</table>

<!-- Tableau des articles -->
<table class="main-table">
    <thead>
    <tr>
        <th style="width: 35%; text-align: left;">Désignation</th>
        <th style="width: 12%; text-align: center;">N° Lot</th>
        <th style="width: 13%; text-align: center;">D. Pérempt.</th>
        <th style="width: 12%; text-align: center;">Financement</th>
        <th style="width: 8%; text-align: center;">Qté</th>
        <th style="width: 10%; text-align: right;">P. Unitaire</th>
        <th style="width: 10%; text-align: right;">P. Total</th>
    </tr>
    </thead>
    <tbody>
    @php $total = 0; @endphp
    @foreach($commande->products as $article)
        @php
            $sousTotal = $article->quantite * $article->product->price;
            $total += $sousTotal;

           $lotPhysique = $article->product->lot_prioritaire;
        @endphp
        <tr>
            <!-- Désignation -->
            <td class="text-left">
                <strong style="color: #2c3e50; display: block; font-size: 12px;">{{ $article->product->intitule }}</strong>
                <small style="color: #7f8c8d;">Ref: {{ $article->product->reference }}</small>
            </td>

            <!-- N° Lot extrait du lot spécifique consommé -->
            <td class="text-center" style="font-family: monospace;">{{ $lotPhysique->num_lot ?? '-' }}</td>

            <!-- Date de Péremption rattachée au lot spécifique consommé -->
            <td class="text-center">
                {{ isset($lotPhysique->date_peremption) ? date('d/m/Y', strtotime($lotPhysique->date_peremption)) : '-' }}
            </td>

            <!-- Financement -->
            <td class="text-center">{{ $article->product->financement ?? '-' }}</td>

            <!-- Quantité -->
            <td class="text-center" style="font-weight: bold;">{{ $article->quantite }}</td>

            <!-- Prix Unitaire -->
            <td class="text-right">{{ number_format($article->product->price, 0, ',', ' ') }}</td>

            <!-- Prix Total -->
            <td class="text-right" style="font-weight: bold; color: #2c3e50;">{{ number_format($sousTotal, 0, ',', ' ') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- Logique de calcul des taxes -->
@php
    $tauxTVA = 0.1925;
    $appliquerTVA = false; // Ajustez à true si votre business model intègre la TVA
    $montantTVA = $appliquerTVA ? $total * $tauxTVA : 0;
    $totalTTC = $total + $montantTVA;
@endphp

<!-- Bloc de résumé financier -->
<div class="totals-container">
    <table class="totals-table">
        <tr>
            <td>Total HT</td>
            <td class="text-right">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
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

<!-- Reliquat éventuel -->
@if($commande->rest_to_pay > 0)
    <div class="rest-pay">
        <strong>Reste à payer : {{ number_format($commande->rest_to_pay, 0, ',', ' ') }} FCFA</strong>
    </div>
@endif

<!-- Pied de page -->
<div class="footer">
    B.p.: 554 N’déré — Tél/Fax: +237 222 25 29 28 — Email: info@frps-ad.cm — N° Cont: M12101243909J<br>
    <strong>Merci pour votre confiance.</strong>
</div>

</body>
</html>
