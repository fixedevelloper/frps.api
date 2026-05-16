<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        logger($this->user());
        // Récupère l'ID de l'utilisateur connecté pour l'exception d'unicité de l'email/téléphone
        $userId = $this->user()->id;


        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'sometimes|string|unique:users,phone,' . $userId,
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'departement_id' => 'nullable|exists:departements,id',
        ];
    }
}
