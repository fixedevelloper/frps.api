<div class="container text-center py-5">
    <h1 class="text-danger">Paiement échoué !</h1>
    <p>Le paiement n'a pas pu être traité.</p>
    @if($reference)
        <p>Référence de paiement : <strong>{{ $reference }}</strong></p>
    @endif

    <a href="{{env('FRONTEND_URL')}}/orders/list" class="btn btn-primary mt-3">Réessayer</a>
    <a href="{{env('FRONTEND_URL')}}/" class="btn btn-secondary mt-3">Retour à l'accueil</a>
</div>
