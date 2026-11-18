<?php

use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\DashboardRedirectController;
use App\Http\Controllers\Backoffice\Gudang;
use App\Http\Controllers\Backoffice\Marketing;
use App\Http\Controllers\Backoffice\NotificationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - PT Duta Mitra Luhur (Duta Rubber)
|--------------------------------------------------------------------------
*/

// PBI 04 - Akses Company Profile Tanpa Login
Route::get('/', [CompanyController::class, 'index'])->name('home');
Route::get('/tentang-pabrik', [CompanyController::class, 'about'])->name('about');
Route::get('/spesifikasi-teknis', [CompanyController::class, 'technicalSpecs'])->name('specs');
Route::get('/kontak', [CompanyController::class, 'contact'])->name('contact');

// PBI 05 & PBI 06 - Katalog Produk, Filter, Pencarian, & Detail Produk
Route::get('/katalog', [ProductController::class, 'index'])->name('catalog.index');
Route::get('/katalog/{slug}', [ProductController::class, 'show'])->name('catalog.show');

// PBI 09, PBI 10, PBI 11 - Form Tanya Ketersediaan, Validasi & WA Redirect
Route::get('/tanya-ketersediaan', [InquiryController::class, 'create'])->name('inquiry.create');
Route::post('/tanya-ketersediaan', [InquiryController::class, 'store'])->name('inquiry.store');
Route::get('/inquiry/sukses/{number}', [InquiryController::class, 'success'])->name('inquiry.success');

/*
|--------------------------------------------------------------------------
| Backoffice (Sprint 2) - Marketing & Gudang
|--------------------------------------------------------------------------
*/
Route::prefix('backoffice')->name('backoffice.')->group(function () {

    // PBI 14 - Login & RBAC
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::middleware('role:marketing,gudang')->group(function () {
            Route::get('/', DashboardRedirectController::class)->name('home');

            Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
            Route::post('notifikasi/{id}/baca', [NotificationController::class, 'read'])->name('notifications.read');
        });

        // ---------------- MARKETING ----------------
        Route::middleware('role:marketing')->prefix('marketing')->name('marketing.')->group(function () {
            Route::get('dashboard', Marketing\DashboardController::class)->name('dashboard');

            // PBI 15 - Manajemen Inquiry
            Route::get('inquiry', [Marketing\InquiryController::class, 'index'])->name('inquiries.index');
            Route::get('inquiry/{inquiry}', [Marketing\InquiryController::class, 'show'])->name('inquiries.show');
            Route::post('inquiry/{inquiry}/tindak-lanjut', [Marketing\InquiryFollowUpController::class, 'store'])->name('inquiries.follow-ups.store');

            // PBI 16 - Ajukan verifikasi ke Gudang
            Route::post('inquiry/{inquiry}/ajukan-verifikasi', [Marketing\VerificationRequestController::class, 'store'])->name('inquiries.verification.store');

            // PBI 17 - Tandai Ditindaklanjuti
            Route::post('inquiry/{inquiry}/ditindaklanjuti', [Marketing\InquiryController::class, 'markFollowedUp'])->name('inquiries.mark-followed-up');

            // PBI 24 - Konten katalog & company profile
            Route::resource('kategori', Marketing\CategoryController::class)
                ->except('show')
                ->parameters(['kategori' => 'category'])
                ->names('categories');

            Route::resource('produk', Marketing\ProductController::class)
                ->except(['show', 'destroy']) // produk dinonaktifkan, bukan dihapus
                ->parameters(['produk' => 'product'])
                ->names('products');
            Route::patch('produk/{product}/status', [Marketing\ProductController::class, 'toggle'])->name('products.toggle');
            Route::delete('produk/{product}/foto/{image}', [Marketing\ProductController::class, 'destroyImage'])->name('products.images.destroy');

            Route::get('profil-perusahaan', [Marketing\CompanyProfileController::class, 'edit'])->name('company-profile.edit');
            Route::put('profil-perusahaan', [Marketing\CompanyProfileController::class, 'update'])->name('company-profile.update');
        });

        // ---------------- GUDANG ----------------
        Route::middleware('role:gudang')->prefix('gudang')->name('gudang.')->group(function () {
            Route::get('dashboard', Gudang\DashboardController::class)->name('dashboard');

            // PBI 16 & 17 - Daftar pengajuan & pencatatan hasil verifikasi
            Route::get('verifikasi', [Gudang\VerificationController::class, 'index'])->name('verifications.index');
            Route::get('verifikasi/{verificationRequest}', [Gudang\VerificationController::class, 'show'])->name('verifications.show');
            Route::put('verifikasi/{verificationRequest}', [Gudang\VerificationController::class, 'update'])->name('verifications.update');
        });
    });
});
