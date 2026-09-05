<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DepartmentManagementController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureSessionFreshness;
use App\Http\Middleware\SecurityHeaders;



/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Halaman yang dapat diakses tanpa authentication.
|
*/

Route::get('/', function () {
    return view('welcome');
})
    ->middleware(SecurityHeaders::class)
    ->name('home');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
|
| Seluruh halaman internal BMC SmartFind hanya dapat diakses
| oleh user yang sudah login.
|
*/

Route::middleware([
    SecurityHeaders::class,
    'auth',
    EnsureActiveAccount::class,
    EnsureSessionFreshness::class,
])->group(function () {

/*
|--------------------------------------------------------------------------
| Document Revision History
|--------------------------------------------------------------------------
*/

Route::get(
    '/documents/{document}/revisions',
    [DocumentVersionController::class, 'index']
)
    ->whereNumber('document')
    ->name('documents.revisions.index');


/*
|--------------------------------------------------------------------------
| Upload New Revision
|--------------------------------------------------------------------------
*/

Route::get(
    '/documents/{document}/revisions/create',
    [DocumentVersionController::class, 'create']
)
    ->whereNumber('document')
    ->name('documents.revisions.create');


Route::post(
    '/documents/{document}/revisions',
    [DocumentVersionController::class, 'store']
)
    ->whereNumber('document')
    ->middleware('throttle:10,1')
    ->name('documents.revisions.store');


/*
|--------------------------------------------------------------------------
| Open Revision
|--------------------------------------------------------------------------
*/

Route::get(
    '/documents/{document}/revisions/{version}/open',
    [DocumentVersionController::class, 'open']
)
    ->whereNumber('document')
    ->whereNumber('version')
    ->middleware('throttle:60,1')
    ->name('documents.revisions.open');


/*
|--------------------------------------------------------------------------
| Download Revision
|--------------------------------------------------------------------------
*/

Route::get(
    '/documents/{document}/revisions/{version}/download',
    [DocumentVersionController::class, 'download']
)
    ->whereNumber('document')
    ->whereNumber('version')
    ->middleware('throttle:60,1')
    ->name('documents.revisions.download');


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )
        ->middleware('throttle:60,1')
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Document Management
    |--------------------------------------------------------------------------
    |
    | PERHATIAN:
    |
    | Semua route document dengan path statis seperti:
    |
    | /documents/create
    | /documents/archived
    | /documents/search/live
    |
    | ditempatkan SEBELUM route:
    |
    | /documents/{document}
    |
    | agar Laravel tidak salah membaca kata "create", "archived",
    | atau "search" sebagai parameter document.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | Active Document Library
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/documents',
        [DocumentController::class, 'index']
    )->name('documents.index');


    /*
    |--------------------------------------------------------------------------
    | Add Document
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/documents/create',
        [DocumentController::class, 'create']
    )->name('documents.create');


    Route::post(
        '/documents',
        [DocumentController::class, 'store']
    )
        ->middleware('throttle:10,1')
        ->name('documents.store');


    /*
    |--------------------------------------------------------------------------
    | Archived Documents
    |--------------------------------------------------------------------------
    |
    | Tidak menggunakan hard delete.
    |
    | Dokumen yang diarsipkan tetap memiliki:
    |
    | - Metadata
    | - File fisik
    | - Favorites
    | - Activity Log
    | - Riwayat arsip
    |
    */

    Route::get(
        '/documents/archived',
        [DocumentController::class, 'archived']
    )->name('documents.archived');


    /*
    |--------------------------------------------------------------------------
    | SmartFind Live Search
    |--------------------------------------------------------------------------
    |
    | Endpoint JSON untuk real-time metadata search.
    |
    */

    Route::get(
        '/documents/search/live',
        [DocumentController::class, 'liveSearch']
    )
        ->middleware('throttle:120,1')
        ->name('documents.search.live');


    /*
    |--------------------------------------------------------------------------
    | Edit Document Metadata
    |--------------------------------------------------------------------------
    |
    | Hanya metadata yang diubah.
    |
    | File fisik tidak:
    |
    | - diganti
    | - dipindahkan
    | - dihapus
    | - ditimpa
    |
    */

    Route::get(
        '/documents/{document}/edit',
        [DocumentController::class, 'edit']
    )
        ->whereNumber('document')
        ->name('documents.edit');


    Route::put(
        '/documents/{document}',
        [DocumentController::class, 'update']
    )
        ->whereNumber('document')
        ->name('documents.update');


    /*
    |--------------------------------------------------------------------------
    | Archive Document
    |--------------------------------------------------------------------------
    |
    | Soft archive menggunakan is_active = false.
    | Tidak ada hard delete terhadap dokumen perusahaan.
    |
    */

    Route::patch(
        '/documents/{document}/archive',
        [DocumentController::class, 'archive']
    )
        ->whereNumber('document')
        ->name('documents.archive');


    /*
    |--------------------------------------------------------------------------
    | Restore Document
    |--------------------------------------------------------------------------
    */

    Route::patch(
        '/documents/{document}/restore',
        [DocumentController::class, 'restore']
    )
        ->whereNumber('document')
        ->name('documents.restore');


    /*
    |--------------------------------------------------------------------------
    | Favorites / Bookmarks
    |--------------------------------------------------------------------------
    |
    | Favorite bersifat personal untuk masing-masing user.
    |
    | Menambah / menghapus favorite tidak mengubah dokumen asli.
    |
    */

    Route::post(
        '/documents/{document}/favorite',
        [FavoriteController::class, 'store']
    )
        ->whereNumber('document')
        ->middleware('throttle:60,1')
        ->name('favorites.store');


    Route::delete(
        '/documents/{document}/favorite',
        [FavoriteController::class, 'destroy']
    )
        ->whereNumber('document')
        ->middleware('throttle:60,1')
        ->name('favorites.destroy');


    /*
    |--------------------------------------------------------------------------
    | Secure Document Open / Preview
    |--------------------------------------------------------------------------
    |
    | File tidak diberikan melalui path storage fisik secara langsung.
    |
    | Laravel melakukan authentication dan pengecekan dokumen
    | sebelum mengirim file kepada browser.
    |
    */

    Route::get(
        '/documents/{document}/open',
        [DocumentController::class, 'open']
    )
        ->whereNumber('document')
        ->middleware('throttle:60,1')
        ->name('documents.open');


    /*
    |--------------------------------------------------------------------------
    | Secure Document Download
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/documents/{document}/download',
        [DocumentController::class, 'download']
    )
        ->whereNumber('document')
        ->middleware('throttle:60,1')
        ->name('documents.download');


    /*
    |--------------------------------------------------------------------------
    | Document Detail
    |--------------------------------------------------------------------------
    |
    | Route dinamis {document} sengaja ditempatkan PALING AKHIR
    | dari kelompok Document Management.
    |
    */

    Route::get(
        '/documents/{document}',
        [DocumentController::class, 'show']
    )
        ->whereNumber('document')
        ->name('documents.show');


    /*
    |--------------------------------------------------------------------------
    | Favorites Library
    |--------------------------------------------------------------------------
    |
    | Halaman personal yang menampilkan dokumen yang difavoritkan
    | oleh user yang sedang login.
    |
    */

    Route::get(
        '/favorites',
        [FavoriteController::class, 'index']
    )->name('favorites.index');


    /*
    |--------------------------------------------------------------------------
    | Category Management
    |--------------------------------------------------------------------------
    |
    | Authorization Super Admin tetap dilakukan di
    | CategoryManagementController.
    |
    */

    Route::get(
        '/categories',
        [CategoryManagementController::class, 'index']
    )->name('categories.index');


    Route::get(
        '/categories/create',
        [CategoryManagementController::class, 'create']
    )->name('categories.create');


    Route::post(
        '/categories',
        [CategoryManagementController::class, 'store']
    )->name('categories.store');


    Route::get(
        '/categories/{category}/edit',
        [CategoryManagementController::class, 'edit']
    )
        ->whereNumber('category')
        ->name('categories.edit');


    Route::put(
        '/categories/{category}',
        [CategoryManagementController::class, 'update']
    )
        ->whereNumber('category')
        ->name('categories.update');


    Route::patch(
        '/categories/{category}/toggle-status',
        [CategoryManagementController::class, 'toggleStatus']
    )
        ->whereNumber('category')
        ->name('categories.toggle-status');




    /*
    |--------------------------------------------------------------------------
    | Department Management
    |--------------------------------------------------------------------------
    |
    | Pengelolaan struktur departemen BMC SmartFind.
    |
    | Authorization Super Admin tetap dilakukan di
    | DepartmentManagementController.
    |
    */

    Route::get(
        '/departments',
        [DepartmentManagementController::class, 'index']
    )->name('departments.index');


    Route::get(
        '/departments/create',
        [DepartmentManagementController::class, 'create']
    )->name('departments.create');


    Route::post(
        '/departments',
        [DepartmentManagementController::class, 'store']
    )->name('departments.store');


    Route::get(
        '/departments/{department}/edit',
        [DepartmentManagementController::class, 'edit']
    )
        ->whereNumber('department')
        ->name('departments.edit');


    Route::put(
        '/departments/{department}',
        [DepartmentManagementController::class, 'update']
    )
        ->whereNumber('department')
        ->name('departments.update');


    Route::patch(
        '/departments/{department}/toggle-status',
        [DepartmentManagementController::class, 'toggleStatus']
    )
        ->whereNumber('department')
        ->name('departments.toggle-status');


    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    |
    | Approval dan pengelolaan status akun.
    |
    | Authorization role tetap ditangani oleh
    | UserManagementController.
    |
    */

    Route::get(
        '/users',
        [UserManagementController::class, 'index']
    )->name('users.index');


    Route::patch(
        '/users/{user}/approve',
        [UserManagementController::class, 'approve']
    )
        ->whereNumber('user')
        ->name('users.approve');


    Route::patch(
        '/users/{user}/reject',
        [UserManagementController::class, 'reject']
    )
        ->whereNumber('user')
        ->name('users.reject');


    Route::patch(
        '/users/{user}/deactivate',
        [UserManagementController::class, 'deactivate']
    )
        ->whereNumber('user')
        ->name('users.deactivate');


    Route::patch(
        '/users/{user}/activate',
        [UserManagementController::class, 'activate']
    )
        ->whereNumber('user')
        ->name('users.activate');


    /*
    |--------------------------------------------------------------------------
    | Activity Log / Audit Trail
    |--------------------------------------------------------------------------
    |
    | Menampilkan aktivitas penting seperti:
    |
    | - Upload Document
    | - View Document
    | - Open Document
    | - Download Document
    | - Favorite
    | - Edit Document
    | - Archive Document
    | - Restore Document
    | - User Approval
    | - Category Management
    |
    */

    Route::get(
        '/activity-logs',
        [ActivityLogController::class, 'index']
    )->name('activity-logs.index');


    /*
    |--------------------------------------------------------------------------
    | User Profile
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');


    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');


    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');
});


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Route authentication Laravel Breeze:
|
| - Login
| - Register
| - Forgot Password
| - Reset Password
| - Logout
| - Email verification (jika digunakan)
|
*/

Route::middleware(
    SecurityHeaders::class
)->group(function () {

    require __DIR__.'/auth.php';
});