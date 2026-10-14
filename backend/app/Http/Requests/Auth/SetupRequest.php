<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (User::exists()) {
            abort(403, 'A configuração inicial já foi concluída.');
        }
        if (! in_array($this->ip(), ['127.0.0.1', '::1'])) {
            abort(403, 'Crie o primeiro usuário pelo comando radar:user no servidor.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:12|max:128'
        ];
    }
}
