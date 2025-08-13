<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Departement;
use App\Models\Image;
use App\Models\Litige;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{

    public function departements(Request $request)
    {
        $departements = Departement::all();
        return Helpers::success($departements);
    }
    public function cities(Request $request,$departement_id)
    {
        $cities = City::query()->where(['departement_id'=>$departement_id])->get();
        return Helpers::success($cities);
    }
    public function getCustomers(Request $request)
    {
        $litiges = User::with([
            'image',
            'city','departement'
        ])->where(['user_type'=>User::CUSTOMER_TYPE])->get();

        $items = $litiges->map(function ($payment) {
            return [
                'id' => $payment->id,
                'name' => $payment->name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                'date' => $payment->created_at,
                'image' => $payment->image ? $payment->image->src : null,
                'departement' => $payment->departement
                    ? $payment->departement->name
                    : null,
                'city' => $payment->city
                    ? $payment->city->name
                    : null,
            ];
        });

        return Helpers::success($items);
    }
    public function getAgents(Request $request)
    {
        $litiges = User::with([
            'image',
            'city','departement'
        ])->where(['user_type'=>User::AGENT_TYPE])->get();

        $items = $litiges->map(function ($payment) {
            return [
                'id' => $payment->id,
                'name' => $payment->name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                'date' => $payment->created_at,
                'image' => $payment->image ? $payment->image->src : null,
                'departement' => $payment->departement
                    ? $payment->departement->name
                    : null,
                'city' => $payment->city
                    ? $payment->city->name
                    : null,
            ];
        });

        return Helpers::success($items);
    }
    public function storeAgent(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'type' => 'required|string',
            'image' => 'nullable|image|max:2048' // max 2 Mo
        ]);


        // Upload image si présente
        $imagePath = null;


        $category= User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'user_type' => $validated['type']=='chauffeur'?User::DRIVER_TYPE: User::AGENT_TYPE,
            'password' => Hash::make('123456789'),
        ]);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $image=Image::create([
                'src'=>$imagePath
            ]);
            $category->image_id=$image->id;
            $category->save();
        }
        return Helpers::success($category,'Agent enregistré');
    }
}
