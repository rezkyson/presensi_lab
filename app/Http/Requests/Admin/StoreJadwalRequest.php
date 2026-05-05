<?php

namespace App\Http\Requests\Admin;

use App\Models\Jadwal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJadwalRequest extends FormRequest
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
        return [
            'kelas_id' => ['required', 'integer', Rule::exists('kelas', 'id')],
            'dosen_id' => ['required', 'integer', Rule::exists('dosen', 'id')],
            'mata_kuliah' => ['required', 'string', 'max:160'],
            'hari' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruangan' => [
                'required',
                'string',
                'max:100',
                Rule::exists('ruangan', 'nama')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateScheduleConflicts($validator);
            },
        ];
    }

    protected function validateScheduleConflicts(Validator $validator, ?int $ignoreId = null): void
    {
        $baseQuery = Jadwal::query()
            ->where('hari', $this->input('hari'))
            ->where('jam_mulai', '<', $this->input('jam_selesai'))
            ->where('jam_selesai', '>', $this->input('jam_mulai'))
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId));

        if ((clone $baseQuery)->where('dosen_id', $this->integer('dosen_id'))->exists()) {
            $validator->errors()->add('dosen_id', 'Dosen sudah memiliki jadwal pada rentang waktu tersebut.');
        }

        if ((clone $baseQuery)->where('kelas_id', $this->integer('kelas_id'))->exists()) {
            $validator->errors()->add('kelas_id', 'Kelas sudah memiliki jadwal pada rentang waktu tersebut.');
        }

        if ((clone $baseQuery)->where('ruangan', $this->input('ruangan'))->exists()) {
            $validator->errors()->add('ruangan', 'Ruangan sudah digunakan pada rentang waktu tersebut.');
        }
    }
}
