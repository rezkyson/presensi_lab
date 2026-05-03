<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDosenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dosen = $this->route('dosen');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($dosen?->user_id),
            ],
            'nip' => [
                'required',
                'string',
                'max:40',
                Rule::unique('dosen', 'nip')->ignore($dosen?->id),
            ],
            'bidang_studi' => ['nullable', 'string', 'max:160'],
        ];
    }
}
