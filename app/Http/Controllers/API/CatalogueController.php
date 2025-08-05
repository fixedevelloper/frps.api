<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commande;
use App\Models\EnterStock;
use App\Models\Image;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProformaGenerated;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatalogueController extends Controller
{
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'intitule' => 'required|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048' // max 2 Mo
        ]);


        // Upload image si présente
        $imagePath = null;


        $category = Category::create([
            'intitule' => $validated['intitule'],
            'parent_id' => $validated['parent_id'],
        ]);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $image = Image::create([
                'src' => $imagePath
            ]);
            $category->image_id = $image->id;
            $category->save();
        }
        return response()->json(['message' => 'Produit enregistré', 'produit' => $category], 201);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'intitule' => 'required|string',
            'categorie' => 'required|string',
            'reference' => 'required|string',
            'suivi_stock' => 'required|string',
            'price' => 'required|numeric',
            'price_buy' => 'required|numeric',
            'lot' => 'nullable|string',
            'presentation' => 'nullable|string',
            'dateFabrication' => 'required|date',
            'datePeremption' => 'required|date',
            'financement' => 'required|string',
            'utilisateurCible' => 'required|string',
            'conditionnement.quantite' => 'required|numeric',
            'conditionnement.unite' => 'required|string',
            'conditionnement.poids' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        $publish = Auth::user()->user_type === User::ADMIN_TYPE;

        $produit = Product::create([
            'intitule' => $validated['intitule'],
            'category_id' => $validated['categorie'],
            'reference' => $validated['reference'],
            'type_stock' => $validated['suivi_stock'],
            'price' => $validated['price'],
            'price_buy' => $validated['price_buy'],
            'lot' => $validated['lot'] ?? null,
            'presentation' => $validated['presentation'] ?? null,
            'date_fabrication' => $validated['dateFabrication'],
            'date_peremption' => $validated['datePeremption'],
            'financement' => $validated['financement'],
            'utilisateur_cible' => $validated['utilisateurCible'],
            'quantite' => $validated['conditionnement']['quantite'],
            'unite' => $validated['conditionnement']['unite'],
            'poids' => $validated['conditionnement']['poids'] ?? null,
            'publish' => $publish,
            'created_by' => Auth::id(),
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');

            $image = Image::create([
                'src' => $imagePath
            ]);

            $produit->update(['image_id' => $image->id]);
        }

        return response()->json([
            'message' => 'Produit enregistré',
            'produit' => $produit->load('image') // si relation définie
        ], 201);
    }

    public function categories(Request $request)
    {
        $categories = [];
        $perPage = $request->input('per_page', 5); // nombre d'éléments par page
        $page = $request->input('page', 1); // numéro de la page

        $paginator = Category::with(['parent', 'image'])->paginate($perPage, ['*'], 'page', $page);

        foreach ($paginator->items() as $cat) {
            $categories[] = [
                'id' => $cat->id,
                'intitule' => $cat->intitule,
                'parent' => $cat->parent ? $cat->parent->intitule : null,
                'image' => $cat->image ? $cat->image->src ?? null : null, // adapte 'url' selon ta colonne image
            ];
        }

        return  response()->json([
            'data' => $categories,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }
    public function all_categories(Request $request)
    {
        $categories = [];


        foreach (Category::all() as $cat) {
            $categories[] = [
                'id' => $cat->id,
                'name' => $cat->intitule
            ];
        }

        return  response()->json([
            'data' => $categories
        ]);
    }
    public function products(Request $request)
    {
        $perPage = $request->input('per_page', 5); // nombre d'éléments par page
        $page = $request->input('page', 1); // numéro de la page

        $paginator = Product::with(['category', 'image'])->where(['publish' => true])->paginate($perPage, ['*'], 'page', $page);

        $products = [];
        foreach ($paginator->items() as $cat) {
            $products[] = [
                'id' => $cat->id,
                'intitule' => $cat->intitule,
                'price' => $cat->price,
                'referenceProduit' => $cat->reference,
                'categorie' => $cat->category ? $cat->category->intitule : null,
                'numeroLot' => $cat->lot,
                'quantiteParUnite' => $cat->quantite,
                'uniteDeMesure' => $cat->unite,
                'poidsDimension' => $cat->poids,
                'financement' => $cat->financement,
                'utilisateurCible' => $cat->utilisateur_cible,
                'dateFabrication' => $cat->date_fabrication,
                'datePeremption' => $cat->date_peremption,
                'status' => $cat->publish ? 'publie' : 'En attente',
                //  'image' => $cat->image ? config('app.url') . 'storage/' . ($cat->image->src ?? '') : null,
                'image' => $cat->image ? ($cat->image->src ?? '') : null,
            ];
        }

        return response()->json([
            'data' => $products,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function productsWaiting(Request $request)
    {
        $perPage = $request->input('per_page', 5); // nombre d'éléments par page
        $page = $request->input('page', 1); // numéro de la page

        $paginator = Product::with(['category', 'image'])->where(['publish' => false])->paginate($perPage, ['*'], 'page', $page);

        $products = [];
        foreach ($paginator->items() as $cat) {
            $products[] = [
                'id' => $cat->id,
                'intitule' => $cat->intitule,
                'price' => $cat->price,
                'referenceProduit' => $cat->reference,
                'categorie' => $cat->category ? $cat->category->intitule : null,
                'numeroLot' => $cat->lot,
                'quantiteParUnite' => $cat->quantite,
                'uniteDeMesure' => $cat->unite,
                'poidsDimension' => $cat->poids,
                'financement' => $cat->financement,
                'utilisateurCible' => $cat->utilisateur_cible,
                'dateFabrication' => $cat->date_fabrication,
                'datePeremption' => $cat->date_peremption,
                'status' => $cat->publish ? 'publie' : 'En attente',
                //  'image' => $cat->image ? config('app.url') . 'storage/' . ($cat->image->src ?? '') : null,
                'image' => $cat->image ? ($cat->image->src ?? '') : null,
            ];
        }

        return response()->json([
            'data' => $products,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function publishProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update([
            'publish' => true,
        ]);
        return Helpers::success($product, 'Statut mis à jour avec succès.');
    }

    public function updateStock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        DB::beginTransaction();
        // Enregistrement dans l'historique d'entrée
        EnterStock::create([
            'quantity' => $validated['quantity'],
            'product_id' => $product->id,
            'created_by' => Auth::id(),
            'previous_quantity' => $product->quantite,
        ]);

        // Mise à jour du stock produit
        $product->increment('quantite', $validated['quantity']);
        DB::commit();
        return response()->json([
            'message' => 'Stock mis à jour avec succès.',
            'product' => $product->fresh(),
        ], 200);
    }
    public function getProductByID(Request $request, $id)
    {
        $cat = Product::findOrFail($id);

        $product = [
            'id' => $cat->id,
            'intitule' => $cat->intitule,
            'price' => $cat->price,
            'referenceProduit' => $cat->reference,
            'categorie' => $cat->category ? $cat->category->intitule : null,
            'category_id' => $cat->category ? $cat->category->id : null,
            'numeroLot' => $cat->lot,
            'quantiteParUnite' => $cat->quantite,
            'uniteDeMesure' => $cat->unite,
            'poidsDimension' => $cat->poids,
            'financement' => $cat->financement,
            'utilisateurCible' => $cat->utilisateur_cible,
           'presentation' => $cat->presentation,
            'dateFabrication' => $cat->date_fabrication,
            'datePeremption' => $cat->date_peremption,
            'status' => $cat->publish ? 'publie' : 'En attente',
            //  'image' => $cat->image ? config('app.url') . 'storage/' . ($cat->image->src ?? '') : null,
            'image' => $cat->image ? ($cat->image->src ?? '') : null,
            'suivi_stock'=>$cat->type_stock
        ];
        return Helpers::success($product, 'Statut mis à jour avec succès.');
    }
    public function updateProduct(Request $request,$id)
    {
        $validated = $request->validate([
            'intitule' => 'required|string',
            'categorie' => 'required|string',
            'reference' => 'required|string',
            'suivi_stock' => 'required|string',
            'price' => 'required|numeric',
            'price_buy' => 'required|numeric',
            'lot' => 'nullable|string',
            'presentation' => 'nullable|string',
            'dateFabrication' => 'required|date',
            'datePeremption' => 'required|date',
            'financement' => 'required|string',
            'utilisateurCible' => 'required|string',
            'conditionnement.quantite' => 'required|numeric',
            'conditionnement.unite' => 'required|string',
            'conditionnement.poids' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);
        $cat = Product::findOrFail($id);
        $publish = Auth::user()->user_type === User::ADMIN_TYPE;

        $cat->fill([
            'intitule' => $validated['intitule'],
            'category_id' => $validated['categorie'],
            'reference' => $validated['reference'],
            'type_stock' => $validated['suivi_stock'],
            'price' => $validated['price'],
            'price_buy' => $validated['price_buy'],
            'lot' => $validated['lot'] ?? null,
            'presentation' => $validated['presentation'] ?? null,
            'date_fabrication' => $validated['dateFabrication'],
            'date_peremption' => $validated['datePeremption'],
            'financement' => $validated['financement'],
            'utilisateur_cible' => $validated['utilisateurCible'],
            'quantite' => $validated['conditionnement']['quantite'],
            'unite' => $validated['conditionnement']['unite'],
            'poids' => $validated['conditionnement']['poids'] ?? null,
            'publish' => $publish,
            'created_by' => Auth::id(),
        ]);

        $cat->save();


        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');

            $image = Image::create([
                'src' => $imagePath
            ]);

            $cat->image_id = $image->id;
            $cat->save();
        }


        return response()->json([
            'message' => 'Produit enregistré',
            'produit' => $cat->load('image') // si relation définie
        ], 201);
    }
    public function importProducts(Request $request)
    {
        logger($request->produits);
        $request->validate([
            'produits' => 'required|array',
        ]);

        foreach ($request->produits as $row) {
            Product::create([
                'intitule' => $row['intitule'] ?? '',
                'category_id' => $row['category_id'] ?? null,
                'reference' => $row['reference'] ?? null,
                'type_stock' => $row['type_stock'] ?? '',
                'price' => $row['price'] ?? 0,
                'price_buy' => $row['price_buy'] ?? 0,
                'lot' => $row['lot'] ?? '',
                'presentation' => $row['presentation'] ?? '',
                'date_fabrication' => $row['dateFabrication'] ?? null,
                'date_peremption' => $row['datePeremption'] ?? null,
                'financement' => $row['financement'] ?? '',
                'utilisateur_cible' => $row['utilisateurCible'] ?? '',
                'quantite' => $row['quantite'] ?? 0,
                'unite' => $row['unite'] ?? '',
                'poids' => $row['poids'] ?? 0,
                'created_by' => auth()->id() ?? 1, // ou autre valeur par défaut
            ]);
        }

        return response()->json(['message' => 'Produits importés avec succès']);
    }

}
