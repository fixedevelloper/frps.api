<div class="container text-center py-5">
    <h1 class="text-success">Paiement réussi !</h1>
    <p>Merci pour votre réservation. Votre paiement a été validé.</p>
    @if($reference)
        <p>Référence de paiement : <strong>{{ $reference }}</strong></p>
    @endif

    <a href="{{env('FRONTEND_URL')}}/" class="btn btn-primary mt-3">Retour à l'accueil</a>
    <a href="{{env('FRONTEND_URL')}}/orders/list" class="btn btn-secondary mt-3">Voir mes commandes</a>
</div>
