<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfilePhotoController extends Controller
{
    /**
     * Photos are served only to their owner through authenticated routes.
     * Never trust user input or database data as an arbitrary filesystem path.
     */
    private function ownedPhotoPath(?string $path, int $userId): bool
    {
        return is_string($path)
            && preg_match(
                '~\Aprofile-photos/'.preg_quote((string) $userId, '~').'/[0-9a-f-]{36}\.(jpg|png|webp)\z~D',
                $path
            ) === 1;
    }

    public function show(Request $request): BinaryFileResponse
    {
        // Personal profile photos remain accessible only to their owners.
        return $this->photoResponse($request->user());
    }

    public function showManagedUser(Request $request, User $user): BinaryFileResponse
    {
        // User Management is Super Admin only. Protect this URL too; a known
        // user ID must never turn a private photo into a public resource.
        abort_unless($request->user()?->isSuperAdmin(), 403);

        return $this->photoResponse($user);
    }

    public function showAuditUser(Request $request, User $user): BinaryFileResponse
    {
        // Same role policy as ActivityLogController@index, also enforced when
        // this URL is requested directly outside the Activity Log page.
        abort_unless($request->user()?->isAdmin(), 403);

        return $this->photoResponse($user);
    }

    private function photoResponse(User $user): BinaryFileResponse
    {
        $path = $user->profile_photo_path;

        abort_unless($this->ownedPhotoPath($path, (int) $user->id), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        // Private, authenticated photo; shared caches must never store it.
        $response = new BinaryFileResponse($disk->path($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], false);

        $response->setPrivate();

        return $response;
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validateWithBag('profilePhoto', [
            'photo' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=4096,max_height=4096',
            ],
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extension === null) {
            return Redirect::route('profile.edit')->withErrors([
                'photo' => 'Format foto tidak didukung.',
            ], 'profilePhoto');
        }

        $disk = Storage::disk('local');
        $path = $file->storeAs(
            'profile-photos/'.$user->id,
            Str::uuid().'.'.$extension,
            'local'
        );

        if (! is_string($path) || $path === '') {
            return Redirect::route('profile.edit')->withErrors([
                'photo' => 'Foto gagal disimpan. Silakan coba lagi.',
            ], 'profilePhoto');
        }

        $oldPath = $user->profile_photo_path;

        try {
            // Do not mass-assign avatar paths from client-submitted data.
            $user->forceFill(['profile_photo_path' => $path])->save();
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        if ($oldPath !== $path && $this->ownedPhotoPath($oldPath, (int) $user->id)) {
            $disk->delete($oldPath);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-photo-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->profile_photo_path;

        // Clear database state before deleting the old file.
        $user->forceFill(['profile_photo_path' => null])->save();

        if ($this->ownedPhotoPath($oldPath, (int) $user->id)) {
            Storage::disk('local')->delete($oldPath);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-photo-removed');
    }
}
