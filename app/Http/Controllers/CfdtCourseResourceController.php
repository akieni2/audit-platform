<?php

namespace App\Http\Controllers;

use App\Models\CfdtCourse;
use App\Models\CfdtCourseResource;
use App\Models\CfdtEnrollment;
use App\Models\CfdtResourceProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CfdtCourseResourceController extends Controller
{
    public function store(Request $request, CfdtCourse $course)
    {
        $this->authorizeEditing($request, $course);
        $data = $request->validate([
            'type' => ['required', Rule::in(['text', 'pdf', 'epub', 'image', 'video', 'video_url'])],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'body' => 'nullable|string|required_if:type,text',
            'external_url' => 'nullable|url|max:2000|required_if:type,video_url',
            'file' => 'nullable|file|max:512000|required_unless:type,text,video_url',
            'estimated_minutes' => 'nullable|integer|min:1|max:10000',
            'required' => 'nullable|boolean',
        ]);
        $this->validateFileType($request, $data['type']);
        $path = null;
        $originalFilename = null;
        $mimeType = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('cfdt/courses/'.$course->id, 'local');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
        }
        $course->resources()->create([
            'type' => $data['type'] === 'video_url' ? 'video' : $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'body' => $data['body'] ?? null,
            'path' => $path,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'external_url' => $data['external_url'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'required' => $request->boolean('required', true),
            'sort_order' => ((int) $course->resources()->max('sort_order')) + 1,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Support pédagogique ajouté au parcours.');
    }

    public function show(Request $request, CfdtCourse $course, CfdtCourseResource $resource)
    {
        $this->ensureCourse($course, $resource);
        $enrollment = $this->authorizeViewing($request, $course);
        $progress = null;
        if ($enrollment) {
            $progress = CfdtResourceProgress::firstOrCreate(
                ['enrollment_id' => $enrollment->id, 'resource_id' => $resource->id],
                ['opened_at' => now()]
            );
            if (! $progress->opened_at) $progress->update(['opened_at' => now()]);
        }

        return view('cfdt.resource', compact('course', 'resource', 'enrollment', 'progress'));
    }

    public function file(Request $request, CfdtCourse $course, CfdtCourseResource $resource)
    {
        $this->ensureCourse($course, $resource);
        $this->authorizeViewing($request, $course);
        abort_unless($resource->path && Storage::disk('local')->exists($resource->path), 404);

        return Storage::disk('local')->response($resource->path, $resource->original_filename, [
            'Content-Type' => $resource->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $resource->original_filename).'"',
        ]);
    }

    public function complete(Request $request, CfdtCourse $course, CfdtCourseResource $resource)
    {
        $this->ensureCourse($course, $resource);
        $enrollment = $this->authorizeViewing($request, $course);
        abort_unless($enrollment, 403);
        CfdtResourceProgress::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'resource_id' => $resource->id],
            ['opened_at' => now(), 'completed_at' => now()]
        );
        $required = $course->resources()->where('required', true)->count();
        $completed = CfdtResourceProgress::where('enrollment_id', $enrollment->id)
            ->whereNotNull('completed_at')->whereHas('resource', fn ($query) => $query->where('required', true))->count();
        $enrollment->update(['progress' => $required ? min(90, (int) round($completed * 90 / $required)) : 90]);

        return redirect()->route('cfdt.show', $course)->with('status', 'Support marqué comme terminé.');
    }

    public function destroy(Request $request, CfdtCourse $course, CfdtCourseResource $resource)
    {
        $this->authorizeEditing($request, $course);
        $this->ensureCourse($course, $resource);
        if ($resource->path) Storage::disk('local')->delete($resource->path);
        $resource->delete();

        return back()->with('status', 'Support pédagogique supprimé.');
    }

    private function authorizeEditing(Request $request, CfdtCourse $course): void
    {
        abort_unless($course->created_by === $request->user()->id && in_array($course->status, ['draft', 'changes_requested'], true), 403);
    }

    private function authorizeViewing(Request $request, CfdtCourse $course): ?CfdtEnrollment
    {
        $user = $request->user();
        if ($course->created_by === $user->id || $user->isInstitutionalSuperAdmin() || in_array($user->cfdt_role, ['validator', 'administrator'], true)) return null;
        abort_unless($course->status === 'published' && $user->cfdt_role === 'learner', 403);

        return CfdtEnrollment::where(['course_id' => $course->id, 'user_id' => $user->id])->firstOrFail();
    }

    private function ensureCourse(CfdtCourse $course, CfdtCourseResource $resource): void
    {
        abort_unless($resource->course_id === $course->id, 404);
    }

    private function validateFileType(Request $request, string $type): void
    {
        if (! $request->hasFile('file')) return;
        $extensions = ['pdf' => ['pdf'], 'epub' => ['epub'], 'image' => ['jpg', 'jpeg', 'png', 'webp'], 'video' => ['mp4', 'webm', 'mov']];
        abort_unless(in_array(strtolower($request->file('file')->getClientOriginalExtension()), $extensions[$type] ?? [], true), 422, 'Le format du fichier ne correspond pas au type de support sélectionné.');
    }
}
