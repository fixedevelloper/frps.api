
    <!-- Importation de Tailwind CSS en ligne -->
    <script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center font-sans">
    <div class="max-w-md w-full">
        <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">

            <!-- Header Section -->
            <div class="bg-green-500 py-10 text-center px-6">
                <div class="mb-4 inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full">
                    <!-- Icone de validation (SVG) -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-white mb-2">Paiement réussi</h1>
                <p class="text-green-100 text-sm opacity-90">Votre transaction a été validée avec succès</p>
            </div>

            <!-- Body Section -->
            <div class="p-8 text-center">
                <p class="text-gray-600 text-lg mb-8">
                    Merci pour votre réservation. <br>
                    <span class="font-medium text-gray-800">Votre commande est maintenant confirmée.</span>
                </p>

                @if($reference)
                    <div class="bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100 ring-1 ring-black/5">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
                            Référence de paiement
                        </span>
                        <div class="text-2xl font-mono font-bold text-gray-900">
                            #{{ $reference }}
                        </div>
                    </div>
            @endif

            <!-- Infos Section -->
                <div class="text-left mb-8">
                    <div class="flex items-center bg-green-50 rounded-xl p-4 border border-green-100">
                        <div class="flex-shrink-0 bg-green-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-green-900">Paiement sécurisé</p>
                            <p class="text-xs text-green-700">Votre transaction a été traitée avec un chiffrement de bout en bout.</p>
                        </div>
                    </div>
                </div>

                <!-- Actions Buttons -->
                <div class="space-y-3">
                    <a href="{{ env('FRONTEND_URL') }}/orders/list"
                       class="flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl transition duration-200 shadow-lg shadow-blue-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Voir mes commandes
                    </a>

                    <a href="{{ env('FRONTEND_URL') }}/"
                       class="flex items-center justify-center w-full bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-600 hover:text-gray-800 font-bold py-4 px-6 rounded-xl transition duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>

        <!-- Help Footer -->
        <p class="text-center mt-8 text-gray-500 text-sm">
            Besoin d’aide ? <a href="#" class="text-blue-600 font-semibold hover:underline">Contactez notre support client</a>.
        </p>
    </div>
</div>

