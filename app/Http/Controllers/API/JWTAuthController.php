<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|integer|max:255',
            'phone' => 'required|string|max:9',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return Helpers::error($validator->errors()->toJson());
        }

        try {
            // Transaction uniquement pour la création utilisateur
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->get('name'),
                'city_id' => $request->get('city_id'),
                'departement_id' => $request->get('departement_id'),
                'email' => $request->get('email'),
                'phone' => $request->get('phone'),
                'password' => Hash::make($request->get('password')),
                'user_type' => User::CUSTOMER_TYPE
            ]);
            $user->email_verified_at = now();
            $user->save();


            // Générer l’URL signée
            $temporarySignedUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addHours(24),
                ['id' => $user->id]
            );

            $url = config('app.frontend.url') . '/auth/verify-email?url=' . urlencode($temporarySignedUrl);

            // Envoi du mail en queue (asynchrone)
            Mail::to($user->email)->send(new VerifyEmailMail($url));
            DB::commit();
            return Helpers::success([
                'message' => 'Utilisateur créé avec succès. Un email de vérification a été envoyé.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::error('Erreur lors de l\'inscription : ' . $e->getMessage());
        }
    }

    // User registration
    public function register2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'required|integer|max:255',
            'phone' => 'required|string|max:9',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()){
            return Helpers::error($validator->errors()->toJson());
        }

        $user = User::create([
            'name' => $request->get('name'),
            'city_id' => $request->get('city_id'),
            'departement_id' => $request->get('departement_id'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'password' => Hash::make($request->get('password')),
            'user_type'=>User::CUSTOMER_TYPE
        ]);

        // Générer une URL signée valable 24h
        $temporarySignedUrl = URL::temporarySignedRoute(
            'verification.verify', // nom de la route backend
            Carbon::now()->addHours(24),
            ['id' => $user->id]
        );

        // Créer l’URL front Angular
        $url = config('app.frontend.url') . '/auth/verify-email?url=' . urlencode($temporarySignedUrl);

        // Envoi du mail
        Mail::to($user->email)->send(new VerifyEmailMail($url));
        return Helpers::success([

        ]);
    }

    // User login
    public function loginCustomer(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return Helpers::unauthorized(401,'Utilisateur non trouvé');
            }

            // Get the authenticated user.
            $user = auth()->user();
            if (($user->user_type != User::CUSTOMER_TYPE)) {
                return Helpers::unauthorized(401,'Utilisateur non trouvé');
            }
            if (!$user->hasVerifiedEmail()) {
                return Helpers::unauthorized(401, 'Votre email n\'est pas encore vérifié');
            }

            // (optional) Attach the role to the token.
            $token = JWTAuth::claims(['role' => $user->role])->fromUser($user);

            return Helpers::success([
                'message'=>'Compte créé avec succès. Vérifiez votre email pour activer le compte',
                'token'=>$token,
                'phone'=>$user->phone,
                'username'=>$user->name
            ]);
        } catch (JWTException $e) {
            return Helpers::error('Could not create token');
        }
    }
    public function login(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return Helpers::unauthorized(401,'Utilisateur non trouvé');
            }

            // Get the authenticated user.
            $user = auth()->user();
            if (($user->user_type == User::CUSTOMER_TYPE)) {
                return Helpers::unauthorized(401,'Utilisateur non trouvé');
            }

            // (optional) Attach the role to the token.
            $token = JWTAuth::claims(['role' => $user->role])->fromUser($user);

            return Helpers::success([

                'token'=>$token,
                'phone'=>$user->phone,
                'username'=>$user->name
            ]);
        } catch (JWTException $e) {
            return Helpers::error('Could not create token');
        }
    }
    // Get authenticated user
    public function getUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        return response()->json(compact('user'));
    }

    // User logout
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }
}
