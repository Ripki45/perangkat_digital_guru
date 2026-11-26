<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\JsonApi\GuruJsonApiResource;
use App\Http\Resources\JsonApi\RombelJsonApiResource;
use App\Http\Resources\JsonApi\StudentJsonApiResource;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmisJsonApiController extends Controller
{
    public function students(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(in_array($user->role, [UserRole::Superadmin, UserRole::WaliKelas], true), 403);

        $students = $user->students()
            ->when($request->boolean('aktif'), fn ($q) => $q->aktif())
            ->orderBy('kelas')
            ->orderBy('nama')
            ->paginate(25);

        return StudentJsonApiResource::collection($students);
    }

    public function showStudent(Request $request, Student $student): StudentJsonApiResource
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            in_array($user->role, [UserRole::Superadmin, UserRole::WaliKelas], true)
                && $student->user_id === $user->id,
            403,
        );

        return new StudentJsonApiResource($student);
    }

    public function gurus(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->role === UserRole::Superadmin, 403);

        $gurus = $user->gurus()
            ->when($request->boolean('aktif'), fn ($q) => $q->aktif())
            ->orderBy('nama')
            ->paginate(25);

        return GuruJsonApiResource::collection($gurus);
    }

    public function rombels(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $rombels = $user->rombels()->orderBy('tingkat')->orderBy('kode')->get();

        return RombelJsonApiResource::collection($rombels);
    }
}
