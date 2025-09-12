<x-mail::message>
    {{-- Header --}}
    <x-slot name="header">
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot>
    # Nouvelle Commande Reçue ! 🎉

    Bonjour {{ $admin_name }},

    Une nouvelle commande a été passée sur la boutique.

    **Détails de la commande :**

    - **Commande N° :** {{ $commande->id }}
    - **Client :** {{ $commande->customer->name }}
    - **Email :** {{ $commande->customer->email }}
    - **Téléphone :** {{ $commande->customer->phone }}
    - **Méthode de paiement :** {{ $commande->payment_method }}
    - **Statut :** {{ $commande->status }}

    ---

    ## Produits commandés :

    | Produit | Quantité | Prix Unitaire | Total |
    |---------|----------|---------------|-------|
    @foreach ($commande->products as $item)
        | {{ $item->product->intitule }} | {{ $item->quantite }} | {{ number_format($item->amount, 0, ',', ' ') }} FCFA | {{ number_format($item->amount * $item->quantite, 0, ',', ' ') }} FCFA |
    @endforeach

    **Montant Total :** {{ number_format($commande->total, 0, ',', ' ') }} FCFA

    <x-mail::button :url="'https://app.monsite.com/admin/orders/' . $commande->id">
        Voir la commande
    </x-mail::button>

    <x-slot name="footer">
        <x-mail::footer>
            Merci,<br>
            {{ config('app.name') }}
        </x-mail::footer>
    </x-slot>
</x-mail::message>
