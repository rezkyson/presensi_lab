<?php

namespace App\Http\Requests\Mahasiswa;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class VerifyFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_MAHASISWA;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['required', 'numeric'],
            'client_distance' => ['nullable', 'numeric'],
        ];
    }
}
