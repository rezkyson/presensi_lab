<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachMahasiswaToKelasRequest extends FormRequest
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
        $kelas = $this->route('kelas');

        return [
            'mahasiswa_id' => [
                'required',
                'integer',
                Rule::exists('mahasiswa', 'id'),
                Rule::unique('kelas_mahasiswa', 'mahasiswa_id')
                    ->where('kelas_id', $kelas?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mahasiswa_id.unique' => 'Mahasiswa sudah terdaftar di kelas ini.',
        ];
    }
}
