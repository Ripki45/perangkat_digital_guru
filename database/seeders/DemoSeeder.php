<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'guru@madrasah.test'],
            [
                'name' => 'Hj. Siti Aminah, S.Pd.I., M.Pd.',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $user->forceFill([
            'name' => 'Hj. Siti Aminah, S.Pd.I., M.Pd.',
            'email_verified_at' => $user->email_verified_at ?? now(),
            'password' => Hash::make('password'),
        ])->save();

        TeacherProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_guru' => 'Hj. Siti Aminah, S.Pd.I., M.Pd.',
                'nip' => '198504122010012003',
                'nuptk' => '8945763658200013',
                'jabatan' => 'Guru Kelas V / Wali Kelas 5A',
                'mata_pelajaran' => 'Guru Kelas',
                'kelas_wali' => '5A',
                'tahun_ajaran' => '2025/2026',
                'semester' => 'Ganjil',
                'motto' => 'Mendidik dengan hati, menumbuhkan cinta ilmu dan akhlak mulia.',
                'nama_madrasah' => 'MI Al-Hikmah',
                'npsn' => '20512345',
                'alamat' => 'Jl. Raya Madrasah No. 12',
                'desa' => 'Sukamaju',
                'kecamatan' => 'Cibadak',
                'kabupaten' => 'Sukabumi',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '43351',
                'telepon' => '0266-123456',
                'email_madrasah' => 'info@mialhikmah.sch.id',
                'website' => 'https://mialhikmah.sch.id',
                'nama_kepala_madrasah' => 'H. Abdul Rahman, S.Pd.I., M.M.',
                'nip_kepala_madrasah' => '197003151998031002',
            ],
        );

        if ($user->students()->doesntExist()) {
            $this->seedStudents($user);
        }

        $this->seedDocuments($user);
    }

    private function seedStudents(User $user): void
    {
        $students = [
            ['Ahmad Fauzan Hidayat', 'L', '1A', '2018-04-12'],
            ['Aisyah Nur Fadilah', 'P', '1A', '2018-07-23'],
            ['Muhammad Rizky Pratama', 'L', '1A', '2018-01-09'],
            ['Nabilah Putri Ramadhani', 'P', '1A', '2018-10-30'],
            ['Dimas Arya Saputra', 'L', '2A', '2017-03-18'],
            ['Faizah Aulia Rahma', 'P', '2A', '2017-08-05'],
            ['Rafi Athallah', 'L', '2A', '2017-11-21'],
            ['Zahra Salsabila', 'P', '2A', '2017-06-14'],
            ['Bagas Aditya Nugraha', 'L', '3A', '2016-02-27'],
            ['Hana Khairunnisa', 'P', '3A', '2016-09-16'],
            ['Ilham Maulana Yusuf', 'L', '3A', '2016-05-03'],
            ['Kezia Anindya Putri', 'P', '3A', '2016-12-11'],
            ['Ardan Hafizh Firdaus', 'L', '4A', '2015-01-25'],
            ['Citra Ayu Lestari', 'P', '4A', '2015-07-08'],
            ['Fikri Ramadhan', 'L', '4A', '2015-10-19'],
            ['Naila Syakirah', 'P', '4A', '2015-04-02'],
            ['Alif Rayhan Maulana', 'L', '5A', '2014-02-14'],
            ['Alya Zahirah Putri', 'P', '5A', '2014-06-28'],
            ['Bintang Ramadhan Saputra', 'L', '5A', '2014-09-09'],
            ['Hafizhah Nur Aini', 'P', '5A', '2014-11-30'],
            ['Farhan Abdillah', 'L', '6A', '2013-03-07'],
            ['Khalisa Putri Aulia', 'P', '6A', '2013-08-17'],
            ['Muhammad Zidan Alfarizi', 'L', '6A', '2013-12-05'],
            ['Siti Nurhaliza', 'P', '6A', '2013-05-22'],
        ];

        $tempat = ['Sukabumi', 'Bogor', 'Cianjur', 'Bandung'];

        foreach ($students as $index => [$nama, $gender, $kelas, $tanggalLahir]) {
            Student::query()->create([
                'user_id' => $user->id,
                'nama' => $nama,
                'nisn' => '00'.str_pad((string) (10000000 + $index + 1), 8, '0', STR_PAD_LEFT),
                'nis' => '2025'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'jenis_kelamin' => $gender,
                'kelas' => $kelas,
                'tempat_lahir' => $tempat[$index % count($tempat)],
                'tanggal_lahir' => $tanggalLahir,
                'nama_wali' => 'Orang Tua '.explode(' ', $nama)[0],
                'no_hp' => '0812'.str_pad((string) (34560000 + $index * 137), 8, '0', STR_PAD_LEFT),
                'alamat' => 'Kp. Cibadak No. '.($index + 3).', Sukamaju',
                'status' => 'Aktif',
            ]);
        }
    }

    private function seedDocuments(User $user): void
    {
        $documents = [
            'logo' => [
                'name' => 'logo-mi-al-hikmah.svg',
                'content' => $this->logoSvg(),
            ],
            'ttd_kepala' => [
                'name' => 'ttd-kepala-madrasah.svg',
                'content' => $this->signatureSvg('H. Abdul Rahman', '#1e3a8a', 18),
            ],
            'ttd_guru' => [
                'name' => 'ttd-guru.svg',
                'content' => $this->signatureSvg('Hj. Siti Aminah', '#0f3d6e', -12),
            ],
            'stempel' => [
                'name' => 'stempel-mi-al-hikmah.svg',
                'content' => $this->stempelSvg(),
            ],
        ];

        foreach ($documents as $type => $document) {
            $path = 'legal/demo/'.$document['name'];
            Storage::disk('public')->put($path, $document['content']);

            LegalDocument::query()->updateOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                [
                    'file_path' => $path,
                    'original_name' => $document['name'],
                    'mime_type' => 'image/svg+xml',
                    'file_size' => strlen($document['content']),
                ],
            );
        }
    }

    private function logoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">
  <defs>
    <linearGradient id="emerald" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0f8a4a"/>
      <stop offset="1" stop-color="#075c31"/>
    </linearGradient>
    <linearGradient id="gold" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#f6dc8c"/>
      <stop offset="1" stop-color="#c99700"/>
    </linearGradient>
  </defs>
  <circle cx="256" cy="256" r="246" fill="url(#emerald)" stroke="url(#gold)" stroke-width="10"/>
  <circle cx="256" cy="256" r="218" fill="none" stroke="#f6e3a1" stroke-width="2.5" stroke-dasharray="4 7" opacity="0.8"/>
  <ellipse cx="256" cy="196" rx="52" ry="78" fill="url(#gold)"/>
  <path d="M256 112c-10 18-34 30-34 62 0 24 15 40 34 40s34-16 34-40c0-32-24-44-34-62z" fill="#fff7dc"/>
  <rect x="232" y="150" width="48" height="8" rx="4" fill="#0f7a43" opacity="0.55"/>
  <path d="M178 210c0-10 8-18 18-18h54c8 0 14-5 14-12 0 7 6 12 14 12h38c10 0 18 8 18 18v10H178z" fill="url(#gold)"/>
  <path d="M148 232h216v12H148z" fill="#f6e3a1" opacity="0.9"/>
  <path d="M256 282c-40-22-92-26-124-18v92c36-6 84 0 124 22 40-22 88-28 124-22v-92c-32-8-84-4-124 18z" fill="#ffffff"/>
  <path d="M256 282v94" stroke="#0f7a43" stroke-width="5" opacity="0.5"/>
  <path d="M176 306c24-2 52 4 68 14M176 330c24-2 52 4 68 14M336 306c-24-2-52 4-68 14M336 330c-24-2-52 4-68 14" stroke="#0f7a43" stroke-width="3" fill="none" opacity="0.35" stroke-linecap="round"/>
  <text x="256" y="430" text-anchor="middle" font-family="Georgia, serif" font-size="34" font-weight="bold" fill="#fff7dc" letter-spacing="3">MI AL-HIKMAH</text>
  <text x="256" y="460" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" fill="#f6e3a1" letter-spacing="2.4">MADRASAH IBTIDAIYAH</text>
  <circle cx="256" cy="478" r="4" fill="url(#gold)"/>
</svg>
SVG;
    }

    private function signatureSvg(string $name, string $color, int $offset): string
    {
        $left = 60 + $offset;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 220" width="640" height="220">
  <rect width="640" height="220" fill="none"/>
  <path d="M{$left} 150 C {$left} 60, 150 40, 170 90 C 188 136, 130 178, 200 150 C 260 126, 250 62, 300 82 C 340 98, 300 156, 360 130 C 410 108, 392 56, 442 78 C 478 94, 450 140, 496 116 C 524 101, 520 74, 552 84"
        fill="none" stroke="{$color}" stroke-width="5" stroke-linecap="round"/>
  <path d="M120 170 C 230 158, 330 186, 520 162" fill="none" stroke="{$color}" stroke-width="2.5" opacity="0.5" stroke-linecap="round"/>
  <text x="640" y="196" text-anchor="end" font-family="Georgia, serif" font-size="26" fill="{$color}" opacity="0.85">{$name}</text>
</svg>
SVG;
    }

    private function stempelSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="400" height="400">
  <circle cx="200" cy="200" r="186" fill="#ffffff" opacity="0.55"/>
  <circle cx="200" cy="200" r="184" fill="none" stroke="#107c41" stroke-width="9"/>
  <circle cx="200" cy="200" r="164" fill="none" stroke="#107c41" stroke-width="2.5"/>
  <path d="M200 74l7 20 21 1-17 13 6 21-17-12-17 12 6-21-17-13 21-1z" fill="#c99700"/>
  <text x="200" y="128" text-anchor="middle" font-family="Georgia, serif" font-size="19" font-weight="bold" fill="#107c41" letter-spacing="1.6">MADRASAH IBTIDAIYAH</text>
  <text x="200" y="205" text-anchor="middle" font-family="Georgia, serif" font-size="38" font-weight="bold" fill="#107c41">MI AL-HIKMAH</text>
  <text x="200" y="238" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" fill="#107c41" letter-spacing="2">KABUPATEN SUKABUMI</text>
  <path d="M120 262h160" stroke="#107c41" stroke-width="2"/>
  <text x="200" y="300" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#107c41" letter-spacing="1.4">KEMENTERIAN AGAMA</text>
  <text x="200" y="322" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#107c41" letter-spacing="1.2">NPSN 20512345</text>
</svg>
SVG;
    }
}
