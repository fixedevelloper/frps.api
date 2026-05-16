<script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center font-sans">
    <div class="max-w-md w-full">
        <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">

            <div class="bg-red-500 py-10 text-center px-6">
                <div class="mb-4 inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-white mb-2">Paiement échoué</h1>
                <p class="text-red-100 text-sm opacity-90">Nous n'avons pas pu traiter votre transaction</p>
            </div>

            <div class="p-8 text-center">
                <p class="text-gray-600 text-lg mb-8">
                    Désolé, une erreur est survenue lors de la validation. <br>
                    <span class="font-medium text-gray-800">Aucun montant n'a été débité.</span>
                </p>

                @if($reference)
                    <div class="bg-red-50 rounded-2xl p-6 mb-8 border border-red-100 ring-1 ring-red-500/10">
                        <span class="text-xs font-semibold text-red-400 uppercase tracking-wider block mb-1">
                            Référence de l'erreur
                        </span>
                        <div class="text-2xl font-mono font-bold text-red-900">
                            #{{ $reference }}
                        </div>
                    </div>
                @endif

                <div class="text-left mb-8">
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <p class="text-sm text-gray-700 leading-relaxed">
                            <span class="font-bold block mb-1 text-gray-900">Que faire ?</span>
                            • Vérifiez vos plafonds bancaires.<br>
                            • Assurez-vous que vos informations sont correctes.<br>
                            • Contactez votre banque si le problème persiste.
                        </p>
                    </div>
                </div>

{{--                <div class="space-y-3">
                    <a href="{{ env('FRONTEND_URL') }}/orders/list"
                       class="flex items-center justify-center w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 px-6 rounded-xl transition duration-200 shadow-lg shadow-red-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Réessayer le paiement
                    </a>

                    <a href="{{ env('FRONTEND_URL') }}/"
                       class="flex items-center justify-center w-full bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-600 hover:text-gray-800 font-bold py-4 px-6 rounded-xl transition duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Retour à l'accueil
                    </a>
                </div>--}}
            </div>
        </div>

        <p class="text-center mt-8 text-gray-500 text-sm">
            Besoin d’aide immédiate ? <a href="#" class="text-red-600 font-semibold hover:underline">Contactez notre support</a>.
        </p>
    </div>
</div>
