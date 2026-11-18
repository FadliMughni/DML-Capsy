<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\ProductCategory;

class CompanyController extends Controller
{
    /**
     * Display Company Profile & Homepage (PBI 04, PBI 07, PBI 08)
     * Konten dibaca dari database (PBI 24).
     */
    public function index()
    {
        $featuredProducts = Product::active()
            ->with(['category', 'primaryImage'])
            ->where('is_featured', true)
            ->take(6)
            ->get();

        $categories = ProductCategory::withCount(['products' => fn ($q) => $q->active()])->get();

        $companyProfile = CompanyProfile::current();
        $stats = $companyProfile->statItems();

        return view('pages.home', compact('featuredProducts', 'categories', 'stats', 'companyProfile'));
    }

    /**
     * Tentang Pabrik & Fasilitas Produksi (PBI 04)
     */
    public function about()
    {
        return view('pages.about', ['companyProfile' => CompanyProfile::current()]);
    }

    /**
     * Panduan Spesifikasi Teknis Kompon Karet
     */
    public function technicalSpecs()
    {
        return view('pages.technical-specs');
    }

    /**
     * Kontak Perusahaan & Penawaran RFQ
     */
    public function contact()
    {
        $products = Product::active()->select('id', 'name')->orderBy('name')->get();
        $companyProfile = CompanyProfile::current();

        return view('pages.contact', compact('products', 'companyProfile'));
    }
}
