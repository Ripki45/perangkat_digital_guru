<?php

use App\Enums\UserRole;
use App\Models\Guru;
use App\Models\RdmScore;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('only superadmin can access gtk routes', function () {
    $wali = User::factory()->create(['role' => UserRole::WaliKelas]);
    $guruMapel = User::factory()->create(['role' => UserRole::GuruMapel]);
    $admin = User::factory()->create(['role' => UserRole::Superadmin]);

    $this->get('/gtk')->assertRedirect('/login');
    $this->actingAs($wali)->get('/gtk')->assertForbidden();
    $this->actingAs($guruMapel)->get('/gtk')->assertForbidden();

    $this->actingAs($admin)
        ->get('/gtk')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Guru/Index')
            ->has('gurus', 0)
        );
});

test('superadmin can crud, import, and load sample gtk data with ownership protection', function () {
    $admin = User::factory()->create(['role' => UserRole::Superadmin]);
    $otherAdmin = User::factory()->create(['role' => UserRole::Superadmin]);

    $this->actingAs($admin)
        ->post('/gtk', [
            'gelarDepan' => 'H.',
            'nama' => 'Ahmad Fauzi',
            'gelarBelakang' => 'S.Pd.I',
            'nik' => '3207140101850001',
            'jk' => 'L',
            'statusPegawai' => 'PNS',
            'jenisPtk' => 'Guru Kelas MI',
            'statusAktif' => 'Aktif',
            'jtm' => 24,
        ])
        ->assertRedirect();

    $guru = Guru::query()->where('user_id', $admin->id)->firstOrFail();
    expect($guru->nama)->toBe('AHMAD FAUZI');
    expect($guru->nik)->toBe('3207140101850001');

    // Ownership protection
    $this->actingAs($otherAdmin)
        ->put("/gtk/{$guru->id}", ['nama' => 'HACKED', 'nik' => '3207140101850009'])
        ->assertForbidden();
    $this->actingAs($otherAdmin)
        ->delete("/gtk/{$guru->id}")
        ->assertForbidden();

    // Update
    $this->actingAs($admin)
        ->put("/gtk/{$guru->id}", [
            'nama' => 'AHMAD FAUZI',
            'nik' => '3207140101850001',
            'jk' => 'L',
            'statusPegawai' => 'PNS',
            'jenisPtk' => 'Guru Kelas MI',
            'statusSertifikasi' => 'Sudah Sertifikasi',
            'jtm' => 28,
        ])
        ->assertRedirect();

    expect($guru->fresh()?->jtm)->toBe(28);
    expect($guru->fresh()?->status_sertifikasi)->toBe('Sudah Sertifikasi');

    // Import batch (updates existing by NIK + inserts new)
    $this->actingAs($admin)
        ->post('/gtk/impor', [
            'gurus' => [
                [
                    'nama' => 'AHMAD FAUZI',
                    'nik' => '3207140101850001',
                    'jtm' => 30,
                ],
                [
                    'nama' => 'SITI RAHMAWATI',
                    'nik' => '3207140202900002',
                    'jk' => 'P',
                    'statusPegawai' => 'PPPK',
                ],
            ],
        ])
        ->assertRedirect();

    expect(Guru::query()->where('user_id', $admin->id)->count())->toBe(2);

    // Load sample
    $this->actingAs($admin)->post('/gtk/sampel')->assertRedirect();
    expect(Guru::query()->where('user_id', $admin->id)->count())->toBeGreaterThan(2);

    // Delete
    $this->actingAs($admin)->delete("/gtk/{$guru->id}")->assertRedirect();
    expect(Guru::query()->find($guru->id))->toBeNull();
});

test('all teaching roles can access rdm and save or import scores with ownership guard', function () {
    $guruMapel = User::factory()->create(['role' => UserRole::GuruMapel]);
    $otherUser = User::factory()->create(['role' => UserRole::WaliKelas]);

    $student = $guruMapel->students()->create([
        'nama' => 'MUHAMMAD RIZQI',
        'nisn' => '0165554443',
        'kelas' => '5-A',
        'tingkat' => '5',
        'status' => 'Aktif',
    ]);

    $this->actingAs($guruMapel)
        ->get('/rdm')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Siswa/Rdm')
            ->has('students', 1)
            ->has('madrasahProfile')
        );

    // Save 13 mapel x semester grid
    $this->actingAs($guruMapel)
        ->put("/rdm/{$student->id}", [
            'scores' => [
                "Al-Qur'an Hadis" => ['1' => 88, '2' => 91],
                'Matematika' => ['1' => 84, '2' => null],
            ],
        ])
        ->assertRedirect();

    expect(RdmScore::query()->where('student_id', $student->id)->count())->toBe(3);

    // Ownership check
    $this->actingAs($otherUser)
        ->put("/rdm/{$student->id}", ['scores' => ['Matematika' => ['1' => 10]]])
        ->assertForbidden();

    // Import via NISN matching
    $this->actingAs($guruMapel)
        ->post('/rdm/impor', [
            'entries' => [
                [
                    'nisn' => '0165554443',
                    'semester' => 3,
                    'scores' => [
                        'IPAS' => 92,
                        'Bahasa Indonesia' => 87,
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    expect(
        RdmScore::query()
            ->where('student_id', $student->id)
            ->where('semester', 3)
            ->where('mapel', 'IPAS')
            ->value('nilai')
    )->toBe(92.0);
});

test('wali kelas and superadmin can access rekap emis, restore backup json, and clear data', function () {
    $guruMapel = User::factory()->create(['role' => UserRole::GuruMapel]);
    $wali = User::factory()->create(['role' => UserRole::WaliKelas]);

    $this->actingAs($guruMapel)->get('/rekap-emis')->assertForbidden();

    $this->actingAs($wali)
        ->get('/rekap-emis')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Siswa/Rekap')
            ->has('students')
            ->has('gurus')
            ->has('rombels', 12)
            ->has('rdmScores')
            ->has('madrasahProfile')
        );

    // Restore full JSON backup
    $this->actingAs($wali)
        ->post('/rekap-emis/restore', [
            'madrasahProfile' => [
                'namaMadrasah' => 'MIN 1 CIAMIS TEST',
                'nsm' => '111132070001',
                'npsn' => '60709999',
                'kemenagKab' => 'Kantor Kemenag Kab. Ciamis',
                'tempatSurat' => 'Ciamis',
                'kepalaNama' => 'Drs. H. Uji Coba',
                'kepalaNip' => '197001011999031001',
                'operatorNama' => 'Operator Test',
                'operatorHp' => '08123456789',
            ],
            'customRombels' => [
                ['tingkat' => '1', 'kode' => '1-A', 'wali' => 'Bu Ani'],
            ],
            'students' => [
                [
                    'id' => 999,
                    'nama' => 'ZAHRA PUTRI',
                    'nisn' => '0198887776',
                    'rombel' => '1-A',
                    'tingkat' => '1',
                    'statusSiswa' => 'Aktif',
                ],
            ],
            'gurus' => [
                [
                    'nama' => 'USTADZ ALI',
                    'nik' => '3207140101800001',
                ],
            ],
            'rdmScores' => [
                '999' => [
                    'Akidah Akhlak' => ['1' => 95],
                ],
            ],
        ])
        ->assertRedirect();

    $wali->refresh();
    expect($wali->teacherProfile?->nsm)->toBe('111132070001');
    expect($wali->rombels()->count())->toBe(1);
    expect($wali->students()->count())->toBe(1);
    expect($wali->gurus()->count())->toBe(1);
    expect($wali->rdmScores()->count())->toBe(1);

    // Clear data
    $this->actingAs($wali)->delete('/rekap-emis/clear')->assertRedirect();

    expect($wali->students()->count())->toBe(0);
    expect($wali->gurus()->count())->toBe(0);
    expect($wali->rdmScores()->count())->toBe(0);
    // Rombel & profile remain intact
    expect($wali->rombels()->count())->toBe(1);
});

test('identitas update saves kop surat emis fields and shares them via inertia', function () {
    $user = User::factory()->create(['role' => UserRole::Superadmin]);

    $this->actingAs($user)
        ->put('/identitas', [
            'nama_guru' => 'Ahmad',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'nama_madrasah' => 'MIN 1 CIAMIS',
            'npsn' => '60708888',
            'nsm' => '111132070001',
            'kemenag_kab' => 'Kantor Kementerian Agama Kab. Ciamis',
            'tempat_surat' => 'Ciamis',
            'operator_nama' => 'Operator MIN 1',
            'operator_hp' => '081200001111',
            'logo_kiri' => 'data:image/png;base64,AAA',
            'logo_kanan' => 'data:image/png;base64,BBB',
        ])
        ->assertRedirect();

    $profile = $user->fresh()?->teacherProfile;
    expect($profile?->nsm)->toBe('111132070001');
    expect($profile?->kemenag_kab)->toBe('Kantor Kementerian Agama Kab. Ciamis');
    expect($profile?->tempat_surat)->toBe('Ciamis');
    expect($profile?->operator_nama)->toBe('Operator MIN 1');
    expect($profile?->operator_hp)->toBe('081200001111');
    expect($profile?->logo_kiri)->toBe('data:image/png;base64,AAA');
    expect($profile?->logo_kanan)->toBe('data:image/png;base64,BBB');

    $this->actingAs($user)
        ->get('/identitas')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('teacherProfile.nsm', '111132070001')
            ->where('teacherProfile.operator_nama', 'Operator MIN 1')
            ->has('account.email')
        );

    // Reset Default MIN 1
    $this->actingAs($user)
        ->post('/identitas/reset-default')
        ->assertRedirect();

    $reset = $user->fresh()?->teacherProfile;
    expect($reset?->nama_madrasah)->toBe('MADRASAH IBTIDAIYAH NEGERI 1 CIAMIS');
    expect($reset?->nsm)->toBe('111132070001');
    expect($reset?->logo_kiri)->toBeNull();

    // Update account login info from /identitas
    $this->actingAs($user)
        ->put('/identitas/akun', [
            'name' => 'Operator EMIS Baru',
            'email' => 'operator.baru@min1ciamis.test',
        ])
        ->assertRedirect();

    expect($user->fresh()?->name)->toBe('Operator EMIS Baru');
    expect($user->fresh()?->email)->toBe('operator.baru@min1ciamis.test');
});

test('beranda renders role-aware dashboard with emis stats for all roles', function () {
    $guruMapel = User::factory()->create(['role' => UserRole::GuruMapel]);
    $admin = User::factory()->create(['role' => UserRole::Superadmin]);

    $this->actingAs($guruMapel)
        ->get('/beranda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beranda')
            ->has('stats.gurus')
        );

    $this->actingAs($admin)
        ->get('/beranda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beranda')
            ->has('stats.gurus')
        );
});

test('biodata data madrasah and mapel rombel pages and crud work properly', function () {
    $guruMapel = User::factory()->create(['role' => UserRole::GuruMapel]);
    $wali = User::factory()->create(['role' => UserRole::WaliKelas]);

    // Data Madrasah
    $this->actingAs($wali)
        ->get('/data-madrasah')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Biodata/DataMadrasah')
            ->has('defaults')
        );

    $this->actingAs($wali)
        ->put('/data-madrasah', [
            'nama_madrasah' => 'MIN 1 CIAMIS BIODATA',
            'nsm' => '111132070001',
            'npsn' => '60708447',
            'kemenag_kab' => 'Kemenag Kab. Ciamis',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'tempat_surat' => 'Ciamis',
        ])
        ->assertRedirect();

    expect($wali->fresh()?->teacherProfile?->nama_madrasah)->toBe('MIN 1 CIAMIS BIODATA');

    // Data Mapel & Rombel
    $this->actingAs($guruMapel)
        ->get('/mapel-rombel')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Siswa/Rombel')
            ->has('mapels', 14)
            ->where('canManageRombel', false)
        );

    $this->actingAs($guruMapel)
        ->post('/mapel', [
            'kode' => 'TAHFIDZ',
            'nama' => "Tahfidz Al-Qur'an",
            'kelompok' => 'Muatan Lokal',
            'kkm' => 80,
            'jtm' => 2,
            'tingkat' => '1-6',
        ])
        ->assertRedirect();

    $mapel = $guruMapel->mapels()->where('kode', 'TAHFIDZ')->firstOrFail();
    expect($mapel->nama)->toBe("Tahfidz Al-Qur'an");

    $this->actingAs($guruMapel)
        ->put("/mapel/{$mapel->id}", [
            'kode' => 'TAHFIDZ',
            'nama' => 'Tahfidz Juz 30',
            'kelompok' => 'Muatan Lokal',
            'kkm' => 82,
            'jtm' => 3,
            'tingkat' => '1-6',
        ])
        ->assertRedirect();

    expect($mapel->fresh()?->kkm)->toBe(82);

    $this->actingAs($wali)
        ->delete("/mapel/{$mapel->id}")
        ->assertForbidden();

    $this->actingAs($guruMapel)
        ->delete("/mapel/{$mapel->id}")
        ->assertRedirect();

    expect($guruMapel->mapels()->where('kode', 'TAHFIDZ')->exists())->toBeFalse();
});
