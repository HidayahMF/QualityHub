<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Favorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Menampilkan dokumen favorit milik user yang sedang login.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();

        $query = Favorite::query()
            ->where('user_id', $userId)
            ->whereHas('document', function ($documentQuery) {
                $documentQuery->where('is_active', true);
            })
            ->with([
                'document.category.department',
                'document.uploader',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                (string) $request->input('search')
            );

            $query->whereHas(
                'document',
                function ($documentQuery) use ($search) {

                    $documentQuery->where(
                        function ($q) use ($search) {

                            $q->where(
                                'title',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'file_name',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'document_code',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'part_number',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'keywords',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhereHas(
                                'category',
                                function ($categoryQuery) use ($search) {

                                    $categoryQuery->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    );
                                }
                            )

                            ->orWhereHas(
                                'category.department',
                                function ($departmentQuery) use ($search) {

                                    $departmentQuery->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    );
                                }
                            );
                        }
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Department
        |--------------------------------------------------------------------------
        */

        if ($request->filled('department')) {

            $query->whereHas(
                'document.category',
                function ($categoryQuery) use ($request) {

                    $categoryQuery->where(
                        'department_id',
                        $request->input('department')
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $query->whereHas(
                'document',
                function ($documentQuery) use ($request) {

                    $documentQuery->where(
                        'category_id',
                        $request->input('category')
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | File Type
        |--------------------------------------------------------------------------
        */

        if ($request->filled('extension')) {

            $query->whereHas(
                'document',
                function ($documentQuery) use ($request) {

                    $documentQuery->where(
                        'file_extension',
                        $request->input('extension')
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Favorites
        |--------------------------------------------------------------------------
        */

        $favorites = $query
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Filter Options
        |--------------------------------------------------------------------------
        */

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        $categories = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        $extensions = Document::query()
            ->where('is_active', true)
            ->whereNotNull('file_extension')
            ->distinct()
            ->orderBy('file_extension')
            ->pluck('file_extension');


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalFavorites = Favorite::query()
            ->where('user_id', $userId)
            ->whereHas(
                'document',
                fn ($q) => $q->where('is_active', true)
            )
            ->count();


        return view(
            'favorites.index',
            compact(
                'favorites',
                'departments',
                'categories',
                'extensions',
                'totalFavorites'
            )
        );
    }


    /**
     * Tambahkan dokumen ke Favorites.
     */
    public function store(
        Request $request,
        Document $document
    ): RedirectResponse {

        abort_unless(
            $document->is_active,
            404
        );


        $favorite = Favorite::firstOrCreate([
            'user_id' => Auth::id(),
            'document_id' => $document->id,
        ]);


        if ($favorite->wasRecentlyCreated) {

            $this->logActivity(
                request: $request,
                document: $document,
                action: 'add_favorite',
                description:
                    Auth::user()->name
                    . ' menambahkan dokumen "'
                    . $document->title
                    . '" ke Favorit.'
            );
        }


        return back()->with(
            'success',
            'Dokumen berhasil ditambahkan ke Favorit.'
        );
    }


    /**
     * Hapus dokumen dari Favorites.
     */
    public function destroy(
        Request $request,
        Document $document
    ): RedirectResponse {

        $favorite = Favorite::query()
            ->where(
                'user_id',
                Auth::id()
            )
            ->where(
                'document_id',
                $document->id
            )
            ->first();


        if ($favorite) {

            $favorite->delete();


            $this->logActivity(
                request: $request,
                document: $document,
                action: 'remove_favorite',
                description:
                    Auth::user()->name
                    . ' menghapus dokumen "'
                    . $document->title
                    . '" dari Favorit.'
            );
        }


        return back()->with(
            'success',
            'Dokumen berhasil dihapus dari Favorit.'
        );
    }


    /**
     * Activity Log.
     */
    private function logActivity(
        Request $request,
        Document $document,
        string $action,
        string $description
    ): void {

        ActivityLog::create([
            'user_id' => Auth::id(),

            'document_id' => $document->id,

            'action' => $action,

            'description' => $description,

            'ip_address' => $request->ip(),

            'user_agent' => $request->userAgent(),

            'metadata' => [
                'document_title' =>
                    $document->title,

                'document_code' =>
                    $document->document_code,

                'part_number' =>
                    $document->part_number,

                'file_name' =>
                    $document->file_name,
            ],
        ]);
    }
}