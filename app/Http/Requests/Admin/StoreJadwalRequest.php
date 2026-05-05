<?php

namespace App\Http\Requests\Admin;

use App\Models\Jadwal;
use DateTimeInterface;
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

        $dosenConflict = (clone $baseQuery)
            ->where('dosen_id', $this->integer('dosen_id'))
            ->first();

        if ($dosenConflict) {
            $validator->errors()->add('dosen_id', $this->conflictMessage('Dosen sudah memiliki jadwal', $dosenConflict));
        }

        $kelasConflict = (clone $baseQuery)
            ->where('kelas_id', $this->integer('kelas_id'))
            ->first();

        if ($kelasConflict) {
            $validator->errors()->add('kelas_id', $this->conflictMessage('Kelas sudah memiliki jadwal', $kelasConflict));
        }

        $roomConflict = (clone $baseQuery)
            ->where('ruangan', $this->input('ruangan'))
            ->first();

        if ($roomConflict) {
            $validator->errors()->add('ruangan', $this->conflictMessage("Ruangan {$this->input('ruangan')} sudah digunakan", $roomConflict));
        }
    }

    private function conflictMessage(string $prefix, Jadwal $jadwal): string
    {
        return sprintf(
            '%s pada %s pukul %s-%s untuk %s.',
            $prefix,
            $jadwal->hari,
            $this->formatTime($jadwal->jam_mulai),
            $this->formatTime($jadwal->jam_selesai),
            $jadwal->mata_kuliah,
        );
    }

    private function formatTime(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        if (preg_match('/(\d{2}:\d{2})/', (string) $value, $matches)) {
            return $matches[1];
        }

        return (string) $value;
    }
}
