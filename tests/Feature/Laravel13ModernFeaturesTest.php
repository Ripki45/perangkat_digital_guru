<?php

use App\Ai\Agents\EmisAssistantAgent;
use App\Enums\UserRole;
use App\Jobs\GenerateEmisNarrativeBatchJob;
use App\Jobs\IndexStudentSemanticEmbeddingJob;
use App\Models\Membership;
use App\Models\RdmScore;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;

test('laravel 13 php attributes work on models, policies, scopes, and commands', function () {
    $user = User::factory()->create(['role' => UserRole::Superadmin]);

    $aktif = $user->students()->create([
        'nama' => 'SISWA AKTIF',
        'kelas' => '4-A',
        'status' => 'Aktif',
    ]);
    $user->students()->create([
        'nama' => 'SISWA ALUMNI',
        'kelas' => '6-A',
        'status' => 'Lulus',
    ]);

    expect(Student::query()->where('user_id', $user->id)->aktif()->pluck('id')->all())
        ->toBe([$aktif->id]);
    expect(Student::query()->where('user_id', $user->id)->byRombel('4-A')->count())
        ->toBe(1);
    expect((new Membership)->getTable())->toBe('team_members');
    expect(Gate::getPolicyFor(Team::class))->toBeInstanceOf(TeamPolicy::class);

    $exitCode = Artisan::call('emis:sync-vectors', ['--user' => $user->id]);
    expect($exitCode)->toBe(0);
});

test('queue routing routes ai embedding and narrative batch jobs to dedicated queues', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => UserRole::WaliKelas]);
    $student = $user->students()->create([
        'nama' => 'AHMAD FAUZAN',
        'kelas' => '5-A',
        'status' => 'Aktif',
    ]);

    IndexStudentSemanticEmbeddingJob::dispatch($user);
    GenerateEmisNarrativeBatchJob::dispatch($user, $student);

    Queue::assertPushedOn('ai-embeddings', IndexStudentSemanticEmbeddingJob::class);
    Queue::assertPushedOn('emis-reports', GenerateEmisNarrativeBatchJob::class);
});

test('laravel ai sdk agent responds on asisten ai endpoint using fake', function () {
    EmisAssistantAgent::fake([
        'Ringkasan MIN 1 Ciamis: ditemukan 1 siswa penerima PIP aktif di kelas 4-A.',
    ]);

    $user = User::factory()->create(['role' => UserRole::WaliKelas]);

    $this->actingAs($user)
        ->postJson('/asisten-ai/tanya', [
            'prompt' => 'Tampilkan ringkasan siswa penerima PIP',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('configured', true)
        ->assertJsonPath('answer', 'Ringkasan MIN 1 Ciamis: ditemukan 1 siswa penerima PIP aktif di kelas 4-A.');

    EmisAssistantAgent::assertPrompted('Tampilkan ringkasan siswa penerima PIP');
});

test('semantic search indexes records via laravel ai embeddings fake and ranks vector matches', function () {
    Embeddings::fake([
        [[0.9, 0.1, 0.0], [0.1, 0.9, 0.0]],
        [[0.88, 0.12, 0.0]],
    ]);

    $user = User::factory()->create(['role' => UserRole::WaliKelas]);
    $user->students()->create([
        'nama' => 'AISYAH PENERIMA PIP',
        'nisn' => '0162345678',
        'kelas' => '4-A',
        'status' => 'Aktif',
        'emis_data' => ['statusPip' => 'Ya', 'hobi' => 'Kaligrafi'],
    ]);
    $user->students()->create([
        'nama' => 'BUDI OLAHRAGA',
        'nisn' => '0162345679',
        'kelas' => '5-B',
        'status' => 'Aktif',
        'emis_data' => ['statusPip' => 'Tidak', 'hobi' => 'Futsal'],
    ]);

    $this->actingAs($user)
        ->postJson('/asisten-ai/sinkron', ['queued' => false])
        ->assertOk()
        ->assertJsonPath('indexed', 2)
        ->assertJsonPath('mode', 'ai-embeddings');

    $this->actingAs($user)
        ->getJson('/asisten-ai/pencarian?query=penerima+bantuan+pip')
        ->assertOk()
        ->assertJsonPath('engine', 'ai-embeddings-cosine')
        ->assertJsonPath('results.0.metadata.nisn', '0162345678');
});

test('json api v1 resources return spec compliant responses with sparse fieldsets, includes, and role guards', function () {
    $wali = User::factory()->create(['role' => UserRole::WaliKelas]);
    $guruMapel = User::factory()->create(['role' => UserRole::GuruMapel]);
    $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);

    $student = $wali->students()->create([
        'nama' => 'ZAHRA KHAIRUNNISA',
        'nisn' => '0191234567',
        'kelas' => '1-A',
        'status' => 'Aktif',
    ]);

    RdmScore::query()->create([
        'user_id' => $wali->id,
        'student_id' => $student->id,
        'mapel' => 'Matematika',
        'semester' => 1,
        'nilai' => 93,
    ]);

    // Role checks
    $this->actingAs($guruMapel)->get('/api/v1/emis/students')->assertForbidden();
    $this->actingAs($wali)->get('/api/v1/emis/gurus')->assertForbidden();
    $this->actingAs($superadmin)->get('/api/v1/emis/gurus')->assertOk();

    // JSON:API single student with sparse fieldset and included relationship
    $response = $this->actingAs($wali)
        ->get("/api/v1/emis/students/{$student->id}?fields[students]=nama,nisn&include=rdmScores");

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('jsonapi.version', '1.1')
        ->assertJsonPath('data.type', 'students')
        ->assertJsonPath('data.id', (string) $student->id)
        ->assertJsonPath('data.attributes.nama', 'ZAHRA KHAIRUNNISA')
        ->assertJsonPath('data.attributes.nisn', '0191234567')
        ->assertJsonMissingPath('data.attributes.rombel')
        ->assertJsonPath('included.0.type', 'rdm-scores')
        ->assertJsonPath('included.0.attributes.mapel', 'Matematika');
});
