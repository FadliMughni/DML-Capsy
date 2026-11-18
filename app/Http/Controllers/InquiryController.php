<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    /**
     * Display Inquiry / RFQ Form (PBI 09)
     */
    public function create(Request $request)
    {
        $selectedProductId = $request->query('product_id');
        $selectedProduct = null;
        if ($selectedProductId) {
            $selectedProduct = Product::active()->find($selectedProductId);
        }

        $products = Product::active()->select('id', 'name')->orderBy('name')->get();
        return view('pages.inquiry-form', compact('products', 'selectedProduct'));
    }

    /**
     * Validate & Store Inquiry (PBI 10, PBI 11)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'company' => 'nullable|string|max:150',
            'phone' => ['required', 'string', 'min:8', 'max:25', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'email' => 'nullable|email|max:150',
            'product_name' => 'required|string|max:200',
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'size' => 'required|string|max:150',
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string|max:50',
            'variant' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1500',
        ], [
            'name.required' => 'Nama lengkap / PIC wajib diisi.',
            'phone.required' => 'Nomor WhatsApp / telepon wajib diisi untuk konfirmasi ketersediaan.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'product_name.required' => 'Nama produk wajib dipilih atau dituliskan.',
            'size.required' => 'Ukuran / dimensi kebutuhan wajib diisi (misal: tebal, panjang, lebar).',
            'quantity.required' => 'Jumlah pesanan wajib diisi.',
            'quantity.min' => 'Jumlah minimal adalah 1.',
        ]);

        // Generate unique inquiry number
        $validated['inquiry_number'] = Inquiry::generateInquiryNumber();
        $validated['status'] = InquiryStatus::Baru;

        // Nomor WhatsApp sales dari company profile (PBI 24), cadangan: config services.sales_whatsapp
        $salesPhone = CompanyProfile::current()->whatsappNumber();

        $inquiry = new Inquiry($validated);
        $whatsappUrl = $inquiry->buildWhatsAppMessage($salesPhone);
        $inquiry->whatsapp_url = $whatsappUrl;
        $inquiry->save();

        // Redirect to success page with WhatsApp link and auto-redirect trigger
        return redirect()->route('inquiry.success', ['number' => $inquiry->inquiry_number])
            ->with('success', 'Permintaan ketersediaan berhasil disimpan dengan nomor ' . $inquiry->inquiry_number)
            ->with('whatsapp_url', $whatsappUrl)
            ->with('auto_redirect', true);
    }

    /**
     * Display Success Confirmation & wa.me Redirect (PBI 11)
     */
    public function success($number)
    {
        $inquiry = Inquiry::where('inquiry_number', $number)->firstOrFail();
        return view('pages.inquiry-success', compact('inquiry'));
    }
}

