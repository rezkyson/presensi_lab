<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MahasiswaImport implements ToCollection, WithHeadingRow
{
    private int $successCount = 0;

    /**
     * @var array<int, array{row: int, errors: array<int, string>, values: array<string, mixed>}>
     */
    private array $failures = [];

    /**
     * @var array<int, string>
     */
    private array $seenNim = [];

    public function __construct(private readonly string $defaultPassword) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row->toArray());

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $errors = $this->validateRow($data);

            if ($errors) {
                $this->failures[] = [
                    'row' => $rowNumber,
                    'errors' => $errors,
                    'values' => $data,
                ];

                continue;
            }

            DB::transaction(function () use ($data) {
                $kelasIds = $this->kelasIds($data['kelas']);

                $user = User::query()->create([
                    'name' => $data['nama'],
                    'email' => $data['email'],
                    'password' => Hash::make($this->defaultPassword),
                    'role' => User::ROLE_MAHASISWA,
                    'is_active' => true,
                ]);

                $mahasiswa = Mahasiswa::query()->create([
                    'user_id' => $user->id,
                    'nim' => $data['nim'],
                    'prodi' => $data['prodi'],
                    'angkatan' => (int) $data['angkatan'],
                    'wajah_terdaftar' => false,
                ]);

                $mahasiswa->kelas()->sync($kelasIds);
            });

            $this->successCount++;
            $this->seenNim[] = $data['nim'];
        }
    }

    public function result(): array
    {
        return [
            'success' => $this->successCount,
            'failed' => count($this->failures),
            'failures' => $this->failures,
        ];
    }

    private function normalizeRow(array $row): array
    {
        return [
            'nim' => trim((string) ($row['nim'] ?? '')),
            'nama' => trim((string) ($row['nama'] ?? '')),
            'email' => trim((string) ($row['email'] ?? '')),
            'prodi' => trim((string) ($row['prodi'] ?? '')),
            'angkatan' => trim((string) ($row['angkatan'] ?? '')),
            'kelas' => trim((string) ($row['kelas'] ?? '')),
        ];
    }

    private function isEmptyRow(array $data): bool
    {
        return collect($data)->every(fn ($value) => $value === '');
    }

    /**
     * @return array<int, string>
     */
    private function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'nim' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'prodi' => ['required', 'string', 'max:120'],
            'angkatan' => ['required', 'integer', 'min:2000', 'max:2100'],
            'kelas' => ['nullable', 'string', 'max:255'],
        ]);

        $errors = $validator->errors()->all();

        if (in_array($data['nim'], $this->seenNim, true)) {
            $errors[] = 'NIM duplikat dalam file.';
        }

        if (Mahasiswa::query()->where('nim', $data['nim'])->exists()) {
            $errors[] = 'NIM sudah ada di database.';
        }

        if (User::query()->where('email', $data['email'])->exists()) {
            $errors[] = 'Email sudah ada di database.';
        }

        $missingKelas = $this->missingKelasNames($data['kelas']);

        if ($missingKelas) {
            $errors[] = 'Kelas tidak ditemukan: '.implode(', ', $missingKelas).'.';
        }

        return $errors;
    }

    /**
     * @return array<int, int>
     */
    private function kelasIds(string $kelasValue): array
    {
        if ($kelasValue === '') {
            return [];
        }

        return Kelas::query()
            ->whereIn('nama_kelas', $this->kelasNames($kelasValue))
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function missingKelasNames(string $kelasValue): array
    {
        $names = $this->kelasNames($kelasValue);

        if (! $names) {
            return [];
        }

        $existing = Kelas::query()
            ->whereIn('nama_kelas', $names)
            ->pluck('nama_kelas')
            ->all();

        return array_values(array_diff($names, $existing));
    }

    /**
     * @return array<int, string>
     */
    private function kelasNames(string $kelasValue): array
    {
        return collect(explode(',', $kelasValue))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
