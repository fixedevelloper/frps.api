<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Departement;
use App\Models\Image;
use App\Models\Litige;
use App\Models\Notification;
use App\Models\Setting;
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
    public function notifications(Request $request)
    {
        $departements = Notification::all();
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
        ])->where(['user_type'=>User::AGENT_TYPE])->orWhere(['user_type'=>User::DRIVER_TYPE])->get();

        $items = $litiges->map(function ($payment) {
            return [
                'id' => $payment->id,
                'name' => $payment->name,
                'role' => $payment->user_type==User::DRIVER_TYPE?'Chauffeur':'Agent',
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

    // Retourne l'unique Setting
    public function show()
    {
        $setting = Setting::first(); // on suppose qu’il y en a un seul
        return Helpers::success($setting,'Agent enregistré');
    }

    // Met à jour l'unique Setting

        public function update(Request $request)
    {
        $setting = Setting::first(); // on suppose qu'il n'y a qu'une seule ligne

        if (!$setting) {
            return response()->json(['message' => 'Paramètres non trouvés'], 404);
        }
        logger($request->all());
        logger(array_map('strlen', $request->all())); // longueur des champs
        // validation optionnelle
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'stock_alert' => 'nullable|numeric',
            'notification_address' => 'nullable|email',
            'notification_phone' => 'nullable|string',
            'dateline_litige' => 'nullable|numeric',
            'percent_payable' => 'nullable|numeric',
        ]);

        // mettre à jour les champs simples
        $fields = [
            'name', 'phone', 'email', 'address', 'stock_alert',
            'notification_address', 'notification_phone',
            'dateline_litige', 'percent_payable'
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $setting->$field = $request->input($field);
            }
        }

        // gérer le logo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('logos', 'public'); // stockage dans storage/app/public/logos
            $setting->logo = '/storage/' . $path;
        }

        $setting->save();

        return Helpers::success($setting,'Agent enregistré');
    }
}
