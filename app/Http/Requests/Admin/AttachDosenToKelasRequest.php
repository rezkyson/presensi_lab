<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachDosenToKelasRequest extends FormRequest
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
            'dosen_id' => ['required', 'integer', Rule::exists('dosen', 'id')],
            'mata_kuliah' => [
                'required',
                'string',
                'max:160',
                Rule::unique('kelas_dosen', 'mata_kuliah')
                    ->where('kelas_id', $kelas?->id)
                    ->where('dosen_id', $this->input('dosen_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mata_kuliah.unique' => 'Dosen sudah menjadi pengampu mata kuliah ini di kelas tersebut.',
        ];
    }
}
