<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKelasRequest extends FormRequest
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
            'nama_kelas' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kelas', 'nama_kelas')
                    ->where('prodi', $this->input('prodi'))
                    ->where('semester', $this->input('semester'))
                    ->where('tahun_akademik', $this->input('tahun_akademik'))
                    ->ignore($kelas?->id),
            ],
            'prodi' => ['required', 'string', 'max:120', Rule::in(['Teknik Informatika', 'Sistem Informasi'])],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'tahun_akademik' => ['required', 'string', 'max:20'],
        ];
    }
}
