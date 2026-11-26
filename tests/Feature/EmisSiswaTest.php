<?php

use App\Enums\UserRole;
use App\Models\RdmScore;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guest cannot access cek nisn, siswa, rombel, or mutasi', function () {
    $this->get('/cek-nisn')->assertRedirect('/login');
    $this->get('/siswa')->assertRedirect('/login');
    $this->get('/rombel')->assertRedirect('/login');
    $this->get('/mutasi')->assertRedirect('/login');
});

test('guru mapel cannot access wali kelas / superadmin kesiswaan routes', function () {
    $guru = User::factory()->create(['role' => UserRole::GuruMapel]);

    $this->actingAs($guru)->get('/siswa')->assertForbidden();
    $this->actingAs($guru)->get('/rombel')->assertForbidden();
    $this->actingAs($guru)->get('/mutasi')->assertForbidden();

    // Cek NISN is accessible after login for any authenticated user
    $this->actingAs($guru)->get('/cek-nisn')->assertOk();
});

test('authenticated user can lookup student by 10-digit nisn with rdm summary', function () {
    $user = User::factory()->create(['role' => UserRole::WaliKelas]);

    $student = $user->students()->create([
        'nama' => 'AISYAH HUMAIRA PUTRI',
        'nisn' => '0162345678',
        'nis' => '202304015',
        'nik' => '3207144809160004',
        'jenis_kelamin' => 'P',
        'kelas' => '4-A',
        'tingkat' => '4',
        'status' => 'Aktif',
        'emis_data' => [
            'ibuNama' => 'Ai Rosmiati',
            'statusPip' => 'Ya',
            'noKip' => 'KIP-32071401',
        ],
    ]);

    RdmScore::query()->create([
        'user_id' => $user->id,
        'student_id' => $student->id,
        'mapel' => "Al-Qur'an Hadis",
        'semester' => 1,
        'nilai' => 90,
    ]);

    RdmScore::query()->create([
        'user_id' => $user->id,
        'student_id' => $student->id,
        'mapel' => 'Matematika',
        'semester' => 1,
        'nilai' => 86,
    ]);

    $this->actingAs($user)
        ->getJson('/cek-nisn/cari?nisn=0162345678')
        ->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('student.nama', 'AISYAH HUMAIRA PUTRI')
        ->assertJsonPath('student.statusPip', 'Ya')
        ->assertJsonPath('rdm.semesters.1', 88)
        ->assertJsonPath('rdm.overall', 88);

    $this->actingAs($user)
        ->getJson('/cek-nisn/cari?nisn=9999999999')
        ->assertNotFound()
        ->assertJsonPath('found', false);
});

test('wali kelas can create, update, import, and delete emis 4.0 students', function () {
    $wali = User::factory()->create(['role' => UserRole::WaliKelas]);

    $this->actingAs($wali)
        ->get('/siswa')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Siswa/Index')
            ->has('rombels', 12)
            ->has('madrasahProfile')
        );

    $this->actingAs($wali)
        ->post('/siswa', [
            'nama' => 'Muhammad Zayn',
            'nisn' => '0191234567',
            'nik' => '3207141205190001',
            'jk' => 'L',
            'tingkat' => '1',
            'rombel' => '1-A',
            'statusSiswa' => 'Aktif',
            'ibuNama' => 'Neng Yuliani',
            'desa' => 'Maparah',
        ])
        ->assertRedirect();

    $student = Student::query()->where('user_id', $wali->id)->firstOrFail();
    expect($student->nama)->toBe('MUHAMMAD ZAYN');
    expect($student->kelas)->toBe('1-A');
    expect($student->emis_data['ibuNama'])->toBe('Neng Yuliani');

    // XLSX EMIS upsert by NISN
    $this->actingAs($wali)
        ->post('/siswa/impor-emis', [
            'students' => [
                [
                    'nama' => 'MUHAMMAD ZAYN AL-FATIH',
                    'nisn' => '0191234567',
                    'nik' => '3207141205190001',
                    'jk' => 'L',
                    'tingkat' => '1',
                    'rombel' => '1-B',
                    'statusSiswa' => 'Aktif',
                ],
                [
                    'nama' => 'NAYLA AZKIA',
                    'nisn' => '0139988776',
                    'nik' => '3207146107130009',
                    'jk' => 'P',
                    'tingkat' => '6',
                    'rombel' => '6-A',
                    'statusSiswa' => 'Aktif',
                ],
            ],
        ])
        ->assertRedirect();

    expect($wali->students()->count())->toBe(2);
    expect($student->fresh()->kelas)->toBe('1-B');

    $this->actingAs($wali)
        ->delete("/siswa/{$student->id}")
        ->assertRedirect();

    expect($wali->students()->count())->toBe(1);
});

test('wali kelas can manage rombels and run batch promotion and graduation', function () {
    $wali = User::factory()->create(['role' => UserRole::WaliKelas]);

    $this->actingAs($wali)->get('/rombel')->assertOk();

    $this->actingAs($wali)
        ->post('/rombel', [
            'tingkat' => '1',
            'nama' => 'C',
            'wali' => 'Ustadz Ahmad',
        ])
        ->assertRedirect();

    $rombel1C = Rombel::query()->where('user_id', $wali->id)->where('kode', '1-C')->firstOrFail();
    expect($rombel1C->wali)->toBe('Ustadz Ahmad');

    $student = $wali->students()->create([
        'nama' => 'SISWA TEST',
        'nisn' => '0191111111',
        'nik' => '3207141205190011',
        'jenis_kelamin' => 'L',
        'kelas' => '1-C',
        'tingkat' => '1',
        'status' => 'Aktif',
    ]);

    // Cannot delete non-empty rombel
    $this->actingAs($wali)->delete("/rombel/{$rombel1C->id}")->assertRedirect();
    expect(Rombel::query()->find($rombel1C->id))->not->toBeNull();

    // Move student to 2-A via batch
    $this->actingAs($wali)
        ->post('/rombel/batch', [
            'student_ids' => [$student->id],
            'target' => '2-A',
        ])
        ->assertRedirect();

    expect($student->fresh()->kelas)->toBe('2-A');
    expect($student->fresh()->tingkat)->toBe('2');

    // Now 1-C is empty and can be deleted
    $this->actingAs($wali)->delete("/rombel/{$rombel1C->id}")->assertRedirect();
    expect(Rombel::query()->find($rombel1C->id))->toBeNull();

    // Graduate via batch
    $this->actingAs($wali)
        ->post('/rombel/batch', [
            'student_ids' => [$student->id],
            'target' => 'LULUS_BATCH',
            'tgl_lulus' => '2026-06-15',
            'ket_lulus' => 'Lulus Ujian MI',
        ])
        ->assertRedirect();

    expect($student->fresh()->status)->toBe('Lulus');
    expect($student->fresh()->emis_data['tglKeluar'])->toBe('2026-06-15');
});

test('superadmin can record mutasi keluar and cannot access other users students', function () {
    $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
    $otherUser = User::factory()->create(['role' => UserRole::WaliKelas]);

    $ownStudent = $superadmin->students()->create([
        'nama' => 'SISWA PINDAH',
        'nisn' => '0151112223',
        'nik' => '3207141205150001',
        'jenis_kelamin' => 'P',
        'kelas' => '5-A',
        'tingkat' => '5',
        'status' => 'Aktif',
    ]);

    $otherStudent = $otherUser->students()->create([
        'nama' => 'SISWA LAIN',
        'nisn' => '0151112224',
        'nik' => '3207141205150002',
        'jenis_kelamin' => 'L',
        'kelas' => '5-A',
        'tingkat' => '5',
        'status' => 'Aktif',
    ]);

    $this->actingAs($superadmin)->get('/mutasi')->assertOk();

    $this->actingAs($superadmin)
        ->post('/mutasi', [
            'student_id' => $ownStudent->id,
            'alasan' => 'Mutasi / Pindah Sekolah',
            'tgl_keluar' => '2026-08-10',
            'no_surat' => 'B-010/MI/2026',
            'sekolah_tujuan' => 'MIN 2 Ciamis',
            'keterangan' => 'Pindah domisili orang tua',
        ])
        ->assertRedirect();

    $ownStudent->refresh();
    expect($ownStudent->status)->toBe('Mutasi Keluar');
    expect($ownStudent->emis_data['sekolahTujuan'])->toBe('MIN 2 Ciamis');

    $this->actingAs($superadmin)
        ->delete("/siswa/{$otherStudent->id}")
        ->assertForbidden();
});
