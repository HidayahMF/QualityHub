<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiSmartSearchController;
use App\Http\Controllers\CategoryManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\DepartmentManagementController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanDocumentController;
use App\Http\Controllers\RetoolingMasterDataController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureSessionFreshness;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/login')
    ->middleware(SecurityHeaders::class)
    ->name('home');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
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

    Route::get(
        '/documents/{document}/revisions/{version}/open',
        [DocumentVersionController::class, 'open']
    )
        ->whereNumber('document')
        ->whereNumber('version')
        ->middleware('throttle:60,1')
        ->name('documents.revisions.open');

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
    | Route statis ditempatkan sebelum route dinamis /documents/{document}.
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/documents',
        [DocumentController::class, 'index']
    )->name('documents.index');

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

    Route::get(
        '/documents/archived',
        [DocumentController::class, 'archived']
    )->name('documents.archived');

    Route::get(
        '/documents/search/live',
        [DocumentController::class, 'liveSearch']
    )
        ->middleware('throttle:120,1')
        ->name('documents.search.live');

    Route::post(
        '/documents/search/ai',
        AiSmartSearchController::class
    )
        ->middleware('throttle:30,1')
        ->name('documents.search.ai');

    /*
    |--------------------------------------------------------------------------
    | Scan / Capture QC
    |--------------------------------------------------------------------------
    |
    | HARUS berada sebelum /documents/{document} agar "scan" tidak
    | dibaca Laravel sebagai parameter document.
    */

    Route::get(
        '/documents/scan',
        [ScanDocumentController::class, 'create']
    )->name('documents.scan.create');

    Route::post(
        '/documents/scan',
        [ScanDocumentController::class, 'store']
    )
        ->middleware('throttle:10,1')
        ->name('documents.scan.store');

    Route::get(
        '/documents/scan/types',
        [ScanDocumentController::class, 'scanTypes']
    )
        ->middleware('throttle:60,1')
        ->name('documents.scan.types.index');

    Route::post(
        '/documents/scan/types',
        [ScanDocumentController::class, 'storeScanType']
    )
        ->middleware('throttle:30,1')
        ->name('documents.scan.types.store');

    Route::patch(
        '/documents/scan/types/{documentScanType}/deactivate',
        [ScanDocumentController::class, 'deactivateScanType']
    )
        ->whereNumber('documentScanType')
        ->middleware('throttle:30,1')
        ->name('documents.scan.types.deactivate');


    /*
    |--------------------------------------------------------------------------
    | Scan Master Data
    |--------------------------------------------------------------------------
    |
    | Endpoint JSON untuk master produksi pada workflow Retooling, Axle Assy,
    | dan Brake Assy. Diletakkan sebelum /documents/{document}.
    |
    */

    Route::get(
        '/documents/scan/master/lines',
        [RetoolingMasterDataController::class, 'lines']
    )
        ->middleware('throttle:120,1')
        ->name('documents.scan.master.lines');


    Route::get(
        '/documents/scan/master/lines/{productionLine}/products',
        [RetoolingMasterDataController::class, 'products']
    )
        ->whereNumber('productionLine')
        ->middleware('throttle:120,1')
        ->name('documents.scan.master.products');


    Route::get(
        '/documents/scan/master/axle/types',
        [RetoolingMasterDataController::class, 'axleTypes']
    )
        ->middleware('throttle:120,1')
        ->name('documents.scan.master.axle-types');


    Route::get(
        '/documents/scan/master/axle/positions',
        [RetoolingMasterDataController::class, 'axlePositions']
    )
        ->middleware('throttle:120,1')
        ->name('documents.scan.master.axle-positions');


    Route::get(
        '/documents/scan/master/brake/part-numbers',
        [RetoolingMasterDataController::class, 'brakePartNumbers']
    )
        ->middleware('throttle:120,1')
        ->name('documents.scan.master.brake-part-numbers');

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

    Route::patch(
        '/documents/{document}/archive',
        [DocumentController::class, 'archive']
    )
        ->whereNumber('document')
        ->name('documents.archive');

    Route::patch(
        '/documents/{document}/restore',
        [DocumentController::class, 'restore']
    )
        ->whereNumber('document')
        ->name('documents.restore');

    Route::delete(
        '/documents/{document}/permanent',
        [DocumentController::class, 'forceDestroy']
    )
        ->whereNumber('document')
        ->name('documents.force-destroy');

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

    Route::get(
        '/documents/{document}/open',
        [DocumentController::class, 'open']
    )
        ->whereNumber('document')
        ->middleware('throttle:60,1')
        ->name('documents.open');

    Route::get(
        '/documents/{document}/download',
        [DocumentController::class, 'download']
    )
        ->whereNumber('document')
        ->middleware('throttle:60,1')
        ->name('documents.download');

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
    */

    Route::get(
        '/favorites',
        [FavoriteController::class, 'index']
    )->name('favorites.index');


    /*
    |--------------------------------------------------------------------------
    | Category Management
    |--------------------------------------------------------------------------
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
        '/categories/{category}/documents',
        [CategoryManagementController::class, 'documents']
    )
        ->whereNumber('category')
        ->middleware('throttle:120,1')
        ->name('categories.documents.index');

    Route::get(
        '/categories/{category}/edit',
        [CategoryManagementController::class, 'edit']
    )
        ->whereNumber('category')
        ->name('categories.edit');

    Route::put(
        '/categories/{category}',
        [CategoryManagementController::class, 'update']
    )->whereNumber('category')
        ->name('categories.update');

    Route::patch(
        '/categories/{category}/deactivate',
        [CategoryManagementController::class, 'deactivate']
    )
        ->whereNumber('category')
        ->name('categories.deactivate');

    Route::patch(
        '/categories/{category}/activate',
        [CategoryManagementController::class, 'activate']
    )
        ->whereNumber('category')
        ->name('categories.activate');

    Route::delete(
        '/categories/{category}',
        [CategoryManagementController::class, 'destroy']
    )
        ->whereNumber('category')
        ->name('categories.destroy');


    /*
    |--------------------------------------------------------------------------
    | Department Management
    |--------------------------------------------------------------------------
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
    */

    Route::get(
        '/users',
        [UserManagementController::class, 'index']
    )->name('users.index');

    Route::patch(
        '/users/{user}/approve',
        [UserManagementController::class, 'approve']
    )->whereNumber('user')->name('users.approve');

    Route::patch(
        '/users/{user}/reject',
        [UserManagementController::class, 'reject']
    )->whereNumber('user')->name('users.reject');

    Route::patch(
        '/users/{user}/deactivate',
        [UserManagementController::class, 'deactivate']
    )->whereNumber('user')->name('users.deactivate');

    Route::patch(
        '/users/{user}/activate',
        [UserManagementController::class, 'activate']
    )->whereNumber('user')->name('users.activate');


    /*
    |--------------------------------------------------------------------------
    | Activity Log / Audit Trail
    |--------------------------------------------------------------------------
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
});


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware(SecurityHeaders::class)->group(function () {
    require __DIR__.'/auth.php';
});
