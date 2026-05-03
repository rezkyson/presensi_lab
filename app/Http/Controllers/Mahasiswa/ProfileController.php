<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\StoreFaceDataRequest;
use App\Models\FaceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $mahasiswa = $request->user()->mahasiswa?->load('faceData');

        return Inertia::render('Mahasiswa/Profile', [
            'profile' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'nim' => $mahasiswa?->nim,
                'prodi' => $mahasiswa?->prodi,
                'angkatan' => $mahasiswa?->angkatan,
                'wajah_terdaftar' => (bool) $mahasiswa?->wajah_terdaftar,
                'face_registered_at' => $mahasiswa?->faceData?->updated_at?->toDateTimeString(),
            ],
            'faceConfig' => [
                'modelPath' => '/models',
                'threshold' => 0.5,
                'descriptorLength' => 128,
            ],
        ]);
    }

    public function storeFace(StoreFaceDataRequest $request)
    {
        $data = $request->validated();

        [$binaryImage, $extension] = $this->decodeBase64Image($data['image_base64']);
        $mahasiswa = $request->user()->mahasiswa;

        if (! $mahasiswa) {
            abort(403);
        }

        DB::transaction(function () use ($mahasiswa, $data, $binaryImage, $extension) {
            $existingPath = $mahasiswa->faceData?->foto_path;
            $path = 'face-data/'.$mahasiswa->id.'/'.Str::uuid().'.'.$extension;

            Storage::disk('local')->put($path, $binaryImage);

            FaceData::updateOrCreate(
                ['mahasiswa_id' => $mahasiswa->id],
                [
                    'foto_path' => $path,
                    'face_descriptor' => array_map('floatval', $data['face_descriptor']),
                ],
            );

            $mahasiswa->update(['wajah_terdaftar' => true]);

            if ($existingPath && $existingPath !== $path) {
                Storage::disk('local')->delete($existingPath);
            }
        });

        return back()->with('success', 'Data wajah berhasil disimpan.');
    }

    public function descriptor(Request $request): JsonResponse
    {
        $mahasiswa = $request->user()->mahasiswa?->load('faceData');

        abort_unless($mahasiswa, 403);

        if (! $mahasiswa->faceData) {
            return response()->json([
                'registered' => false,
                'descriptor' => null,
            ], 404);
        }

        return response()->json([
            'registered' => true,
            'descriptor' => $mahasiswa->faceData->face_descriptor,
            'threshold' => 0.5,
        ]);
    }

    private function decodeBase64Image(string $image): array
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $image, $matches)) {
            throw ValidationException::withMessages([
                'image_base64' => 'Format foto wajah tidak valid.',
            ]);
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'image_base64' => 'Foto wajah gagal diproses.',
            ]);
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'image_base64' => 'Ukuran foto wajah maksimal 5MB.',
            ]);
        }

        return [$binary, $matches[1] === 'png' ? 'png' : 'jpg'];
    }
}
