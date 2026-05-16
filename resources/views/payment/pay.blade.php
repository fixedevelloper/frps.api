<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement - Commande #{{ $order->order_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Importation d'une police plus moderne et d'icônes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --bg: #f8f9fa;
            --text-main: #212529;
            --text-muted: #6c757d;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .payment-card {
            background: white;
            max-width: 400px;
            width: 100%;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
        }

        .icon-box {
            width: 64px;
            height: 64px;
            background: #e7f1ff;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }

        h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 700;
        }

        .order-ref {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
            display: block;
        }

        .amount-container {
            background: #f1f3f5;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 32px;
        }

        .amount-label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .amount-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            width: 100%;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-bottom: 12px;
        }

        .btn-pay {
            background: var(--success);
            color: white;
        }

        .btn-pay:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: transparent;
            color: var(--danger);
            font-size: 14px;
        }

        .btn-cancel:hover {
            background: #fff5f5;
        }

        .secure-footer {
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>

<div class="payment-card">
    <div class="icon-box">
        <i class="ph-bold ph-credit-card"></i>
    </div>

    <h2>Finaliser le paiement</h2>
    <span class="order-ref">Commande #{{ $order->reference }}</span>

    <div class="amount-container">
        <span class="amount-label">Montant à payer</span>
        <div class="amount-value">{{ number_format($order->montant, 0, ',', ' ') }} FCFA</div>
    </div>

    <form method="GET" action="{{ url('/payment/success/'.$order->reference) }}">
        <button type="submit" class="btn btn-pay">
            <i class="ph-bold ph-check-circle"></i>
            Payer maintenant
        </button>
    </form>

    <form method="GET" action="{{ url('/payment/cancel/'.$order->reference) }}">
        <button type="submit" class="btn btn-cancel">
            Annuler la transaction
        </button>
    </form>

    <div class="secure-footer">
        <i class="ph-bold ph-shield-check"></i>
        Paiement 100% sécurisé
    </div>
</div>

</body>
</html>
