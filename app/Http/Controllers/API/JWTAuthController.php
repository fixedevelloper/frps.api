<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
                'code' => $request->get('code'),
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
                return Helpers::unauthorized(401, 'Utilisateur non trouvé');
            }

            // Récupérer l'utilisateur avec ses rôles et permissions
            $user = User::with('roles.permissions', 'permissions')->find(auth()->id());

            // Sécurité : Vérifier le type d'utilisateur
            if ($user->user_type == User::CUSTOMER_TYPE) {
                return Helpers::unauthorized(401, 'Accès réservé au personnel');
            }

            // Optionnel : Ajouter le rôle au token JWT
            $token = JWTAuth::claims(['role' => $user->roles->first()?->name])->fromUser($user);

        // Récupérer uniquement les noms des permissions (tableau de strings)
        // getAllPermissions() est une méthode de Spatie qui combine permissions directes et via rôles
        $permissions = $user->getAllPermissions()->pluck('name');

        return Helpers::success([
            'token'    => $token,
            'phone'    => $user->phone,
            'username' => $user->name,
            'role'     => $user->roles->first()?->name,
            'permissions' => $permissions // Envoyer le tableau ['products.view', 'orders.create', ...]
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
    // Infos du profil connecté
    // -----------------------------
    public function me(Request $request, $id)
    {
        logger("UserID found: " . $id);
        try {
            // 1. Utilisation de findOrFail : lève une ModelNotFoundException si l'ID n'existe pas
            $user = User::with(['country', 'kyc'])->findOrFail($id);

            $ressource=new UserResource($user);
            // Optionnel : Log pour le debug
            logger(json_encode($ressource));

            return Helpers::success(new UserResource($user));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Capture spécifique : Utilisateur non trouvé
            logger("User not found with ID: $id");
            return Helpers::error("Utilisateur introuvable.", 404);

        } catch (\Exception $e) {
            // Capture générale : Erreur serveur, base de données, etc.
            logger("Error in User Controller (me): " . $e->getMessage());
            return Helpers::error("Une erreur interne est survenue.", 500);
        }
    }
    public function profile(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $user = User::with(['country','kyc'])->find($userId);
        return Helpers::success(new UserResource($user));
    }
    public function getUsers(Request $request)
    {
        $users=User::with(['country','kyc'])->get();
        return Helpers::success($users);
    }
    public function changePassword(Request $request)
    {

        try {
            $user = auth()->user();

            // Mise à jour
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Mot de passe modifié avec succès.'
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le développeur
            Log::error("Erreur changement mot de passe ID {$user->id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur interne est survenue. Veuillez réessayer plus tard.'
            ], 500);
        }
    }
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            // Mise à jour des données validées
            $user->update($request->validated());


            // On recharge la relation country pour renvoyer le profil complet à Android
            $user->load(['country','kyc']);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur Update Profile ID {$user->id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de mettre à jour le profil.'
            ], 500);
        }
    }
}
