<?php

use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\RekapController as AdminRekapController;
use App\Http\Controllers\Admin\RuanganController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dosen\MonitorPresensiController;
use App\Http\Controllers\Dosen\RekapController as DosenRekapController;
use App\Http\Controllers\Dosen\SesiAbsensiController;
use App\Http\Controllers\Mahasiswa\AbsensiController;
use App\Http\Controllers\Mahasiswa\JadwalController as MahasiswaJadwalController;
use App\Http\Controllers\Mahasiswa\ProfileController;
use App\Http\Controllers\Mahasiswa\RiwayatController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::patch('/mahasiswa/{mahasiswa}/toggle-active', [MahasiswaController::class, 'toggleActive'])
                ->name('mahasiswa.toggle-active');
            Route::post('/mahasiswa/{mahasiswa}/reset-password', [MahasiswaController::class, 'resetPassword'])
                ->name('mahasiswa.reset-password');
            Route::get('/mahasiswa/import', [MahasiswaController::class, 'importForm'])
                ->name('mahasiswa.import');
            Route::post('/mahasiswa/import', [MahasiswaController::class, 'import'])
                ->name('mahasiswa.import.store');
            Route::resource('mahasiswa', MahasiswaController::class)
                ->except(['show']);
            Route::patch('/dosen/{dosen}/toggle-active', [DosenController::class, 'toggleActive'])
                ->name('dosen.toggle-active');
            Route::post('/dosen/{dosen}/reset-password', [DosenController::class, 'resetPassword'])
                ->name('dosen.reset-password');
            Route::resource('dosen', DosenController::class)
                ->except(['show']);
            Route::patch('/ruangan/{ruangan}/toggle-active', [RuanganController::class, 'toggleActive'])
                ->name('ruangan.toggle-active');
            Route::resource('ruangan', RuanganController::class)
                ->except(['show']);
            Route::post('/kelas/{kelas}/mahasiswa', [KelasController::class, 'attachMahasiswa'])
                ->name('kelas.mahasiswa.attach');
            Route::delete('/kelas/{kelas}/mahasiswa/{mahasiswa}', [KelasController::class, 'detachMahasiswa'])
                ->name('kelas.mahasiswa.detach');
            Route::post('/kelas/{kelas}/dosen', [KelasController::class, 'attachDosen'])
                ->name('kelas.dosen.attach');
            Route::delete('/kelas/{kelas}/dosen/{kelasDosen}', [KelasController::class, 'detachDosen'])
                ->name('kelas.dosen.detach');
            Route::resource('kelas', KelasController::class)
                ->parameters(['kelas' => 'kelas']);
            Route::resource('jadwal', JadwalController::class)
                ->except(['show'])
                ->parameters(['jadwal' => 'jadwal']);
            Route::get('/rekap', [AdminRekapController::class, 'index'])->name('rekap.index');
            Route::get('/rekap/export/pdf', [AdminRekapController::class, 'exportPdf'])->name('rekap.export.pdf');
            Route::get('/rekap/export/excel', [AdminRekapController::class, 'exportExcel'])->name('rekap.export.excel');
        });

    Route::middleware('role:dosen')
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'dosen'])->name('dashboard');
            Route::get('/monitor', [MonitorPresensiController::class, 'index'])->name('monitor.index');
            Route::get('/rekap', [DosenRekapController::class, 'index'])->name('rekap.index');
            Route::get('/rekap/export/pdf', [DosenRekapController::class, 'exportPdf'])->name('rekap.export.pdf');
            Route::get('/rekap/export/excel', [DosenRekapController::class, 'exportExcel'])->name('rekap.export.excel');
            Route::get('/sesi', [SesiAbsensiController::class, 'index'])->name('sesi.index');
            Route::post('/jadwal/{jadwal}/sesi', [SesiAbsensiController::class, 'store'])->name('sesi.store');
            Route::get('/sesi/{sesi}/qr', [SesiAbsensiController::class, 'qr'])->name('sesi.qr');
            Route::get('/sesi/{sesi}/qr-data', [SesiAbsensiController::class, 'qrData'])->name('sesi.qr-data');
            Route::get('/sesi/{sesi}/monitor', [MonitorPresensiController::class, 'show'])->name('sesi.monitor');
            Route::get('/sesi/{sesi}/kehadiran', [MonitorPresensiController::class, 'attendance'])->name('sesi.kehadiran');
            Route::delete('/sesi/{sesi}', [SesiAbsensiController::class, 'destroy'])->name('sesi.destroy');
        });

    Route::get('/mahasiswa/dashboard', [DashboardController::class, 'mahasiswa'])
        ->middleware('role:mahasiswa')
        ->name('mahasiswa.dashboard');

    Route::middleware('role:mahasiswa')
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {
            Route::get('/profil', [ProfileController::class, 'show'])->name('profil.show');
            Route::post('/profil/wajah', [ProfileController::class, 'storeFace'])->name('profil.wajah.store');
            Route::get('/face-descriptor', [ProfileController::class, 'descriptor'])->name('face-descriptor.show');
            Route::get('/absen', [AbsensiController::class, 'index'])->name('absen.index');
            Route::post('/absen/verifikasi-qr', [AbsensiController::class, 'verifyQr'])
                ->middleware('throttle:qr-verification')
                ->name('absen.verifikasi-qr');
            Route::get('/absen/verifikasi-wajah', [AbsensiController::class, 'faceVerification'])->name('absen.verifikasi-wajah.show');
            Route::post('/absen/verifikasi-wajah', [AbsensiController::class, 'verifyFace'])
                ->middleware('throttle:face-verification')
                ->name('absen.verifikasi-wajah.store');
            Route::get('/absen/sukses', [AbsensiController::class, 'success'])->name('absen.sukses');
            Route::get('/absen/gagal', [AbsensiController::class, 'failed'])->name('absen.gagal');
            Route::get('/jadwal', [MahasiswaJadwalController::class, 'index'])->name('jadwal.index');
            Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
        });
});
