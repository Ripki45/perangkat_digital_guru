<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use App\Services\EmisPayloadMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GuruController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $gurus = $user->gurus()->orderBy('nama')->get();

        return Inertia::render('Guru/Index', [
            'gurus' => $gurus->map(fn (Guru $guru): array => $guru->toEmisPayload())->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGuru($request);
        /** @var User $user */
        $user = $request->user();

        $attrs = EmisPayloadMapper::guruAttributesFromEmis($data);
        $user->gurus()->create($attrs);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data GTK {$attrs['nama']} berhasil disimpan permanen.",
        ]);

        return back();
    }

    public function update(Request $request, Guru $guru): RedirectResponse
    {
        abort_unless($guru->user_id === $request->user()->id, 403);

        $data = $this->validateGuru($request);
        $attrs = EmisPayloadMapper::guruAttributesFromEmis($data);
        $guru->update($attrs);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data GTK {$guru->nama} berhasil diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, Guru $guru): RedirectResponse
    {
        abort_unless($guru->user_id === $request->user()->id, 403);

        $nama = $guru->nama;
        $guru->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data GTK {$nama} telah dihapus.",
        ]);

        return back();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'gurus' => ['required', 'array', 'min:1', 'max:1000'],
            'gurus.*.nama' => ['required', 'string', 'max:150'],
        ]);

        $rows = $request->input('gurus', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        /** @var User $user */
        $user = $request->user();
        $imported = 0;

        DB::transaction(function () use ($user, $rows, &$imported): void {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $attrs = EmisPayloadMapper::guruAttributesFromEmis($row);
                if ($attrs['nama'] === '') {
                    continue;
                }

                $nik = $attrs['nik'];
                $npk = $attrs['npk'];

                $existing = null;
                if ($nik) {
                    $existing = $user->gurus()->where('nik', $nik)->first();
                }
                if (! $existing && $npk) {
                    $existing = $user->gurus()->where('npk', $npk)->first();
                }

                if ($existing) {
                    $existing->update($attrs);
                } else {
                    $user->gurus()->create($attrs);
                }

                $imported++;
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Berhasil mengimpor {$imported} data GTK dari Excel.",
        ]);

        return back();
    }

    public function loadSample(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            foreach (EmisPayloadMapper::sampleGurus() as $row) {
                $existing = $user->gurus()->where('nik', $row['nik'])->first();
                $attrs = EmisPayloadMapper::guruAttributesFromEmis($row);
                if ($existing) {
                    $existing->update($attrs);
                } else {
                    $user->gurus()->create($attrs);
                }
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Data sampel Guru & GTK EMIS 4.0 MIN 1 Ciamis berhasil dimuat.',
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGuru(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nik' => ['required', 'regex:/^\d{16}$/'],
            'jk' => ['required', 'in:L,P'],
            'tglLahir' => ['nullable', 'date'],
            'jtm' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]) + $request->all();
    }
}
