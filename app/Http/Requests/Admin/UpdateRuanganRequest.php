<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateRuanganRequest extends StoreRuanganRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ruangan', 'nama')->ignore($this->route('ruangan')),
            ],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
