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
            line-height: 1.4;
        }
        /* Couleurs Thème */
        .text-primary { color: #2c3e50; }
        .bg-light { background-color: #f8f9fa; }

        /* Header & Logos */
        .header-table {
            width: 100%;
            border: none;
            margin-bottom: 40px;
        }
        .logo { width: 150px; }

        /* Infos Client/Entreprise */
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-box {
            width: 45%;
            vertical-align: top;
        }
        .info-title {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 9px;
            color: #7f8c8d;
            margin-bottom: 5px;
            border-bottom: 1px solid #eee;
            padding-bottom: 3px;
        }

        /* Titre Document */
        .doc-title {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }

        /* Tableau Produits */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .main-table th {
            background-color: #2c3e50;
            color: white;
            text-transform: uppercase;
            font-size: 10px;
            padding: 10px;
            text-align: left;
        }
        .main-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Totaux */
        .total-box {
            margin-top: 20px;
            float: right;
            width: 35%;
        }
        .total-row {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .total-grand {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #95a5a6;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td>
            <img src="{{ public_path('images/logo.png') }}" class="logo">
        </td>
        <td class="doc-title">
            PRO FORMA<br>
            <span style="font-size: 12px; font-weight: normal; color: #7f8c8d;">#{{ $commande->reference ?? $commande->id }}</span>
        </td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="info-box">
            <div class="info-title">Émetteur</div>
            <strong>FRPS - AD</strong><br>
            Sis: Interieur hospital regional de ngaoundéré<br>
            Contact : +237 699 087 986<br>
            Email : inf@vfrps-ad.cm
        </td>
        <td style="width: 10%;"></td>
        <td class="info-box">
            <div class="info-title">Adressé à</div>
            <strong>{{ $commande->customer->name }}</strong><br>
            {{ $commande->customer->address ?? 'Adresse non spécifiée' }}<br>
            Tél : {{ $commande->customer->phone ?? 'N/A' }}<br>
            Date : {{ now()->format('d/m/Y') }}
        </td>
    </tr>
</table>

<table class="main-table" style="width: 100%; border-collapse: collapse; margin-vertical: 15px;">
    <thead>
    <tr style="background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; color: #555;">
        <th style="width: 35%; text-align: left; padding: 8px;">Désignation</th>
        <th style="width: 12%; text-align: center; padding: 8px;">N° Lot</th>
        <th style="width: 13%; text-align: center; padding: 8px;">D. Pérempt.</th>
        <th style="width: 12%; text-align: center; padding: 8px;">Financement</th>
        <th style="width: 8%; text-align: center; padding: 8px;">Qté</th>
        <th style="width: 10%; text-align: right; padding: 8px;">P. Unitaire</th>
        <th style="width: 10%; text-align: right; padding: 8px;">P. Total</th>
    </tr>
    </thead>
    <tbody>
    @php $total = 0; @endphp
    @foreach($commande->products as $article)
        @php
            $sousTotal = $article->quantite * $article->product->price;
            $total += $sousTotal;

            // Extraction sécurisée du lot physique affecté à cette ligne d'article
           $lotPhysique = $article->product->lot_prioritaire;
        @endphp
        <tr style="border-bottom: 1px solid #eee;">
            <!-- Désignation -->
            <td style="padding: 10px 8px; text-align: left; vertical-align: middle;">
                <strong style="color: #2c3e50; display: block; font-size: 13px;">{{ $article->product->intitule }}</strong>
                <small style="color: #7f8c8d; font-size: 11px;">Ref: {{ $article->product->reference }}</small>
            </td>

            <!-- N° Lot extrait dynamiquement selon la stratégie appliquée (FIFO/LIFO) -->
            <td style="padding: 10px 8px; text-align: center; vertical-align: middle; font-size: 12px; font-family: monospace;">
                {{ $lotPhysique->num_lot ?? '-' }}
            </td>

            <!-- Date de Péremption rattachée au lot spécifique extrait -->
            <td style="padding: 10px 8px; text-align: center; vertical-align: middle; font-size: 12px;">
                {{ isset($lotPhysique->date_peremption) ? date('d/m/Y', strtotime($lotPhysique->date_peremption)) : '-' }}
            </td>

            <!-- Financement -->
            <td style="padding: 10px 8px; text-align: center; vertical-align: middle; font-size: 12px;">
                {{ $article->product->financement ?? '-' }}
            </td>

            <!-- Quantité -->
            <td style="padding: 10px 8px; text-align: center; vertical-align: middle; font-weight: 600; font-size: 13px;">
                {{ $article->quantite }}
            </td>

            <!-- Prix Unitaire -->
            <td style="padding: 10px 8px; text-align: right; vertical-align: middle; font-size: 12px;">
                {{ number_format($article->product->price, 0, ',', ' ') }}
            </td>

            <!-- Prix Total -->
            <td style="padding: 10px 8px; text-align: right; vertical-align: middle; font-weight: bold; color: #2c3e50; font-size: 13px;">
                {{ number_format($sousTotal, 0, ',', ' ') }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="total-box">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="total-row">Sous-total</td>
            <td class="total-row text-right">{{ number_format($total, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="total-row">Taxe (0%)</td>
            <td class="total-row text-right">0</td>
        </tr>
        <tr>
            <td class="total-grand">TOTAL NET</td>
            <td class="total-grand text-right">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>
</div>

<div style="clear: both; margin-top: 50px;">
    <p><strong>Note :</strong> Cette pro forma est valable pour une durée de 15 jours.</p>
</div>

<div class="footer">
    B.p.: 554 N’déré Tél/Fax .: +237 222 25 29 28 Email: info@frps-ad.cm N Cont: M12101243909J<br>
    Merci pour votre confiance.
</div>

</body>
</html>
