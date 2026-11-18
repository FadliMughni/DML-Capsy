<?php

namespace App\Http\Requests\Backoffice;

use App\Models\CompanyProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', CompanyProfile::current());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'stats' => collect($this->input('stats', []))
                ->filter(fn ($row) => is_array($row) && (filled($row['value'] ?? null) || filled($row['label'] ?? null)))
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'founded_info' => ['nullable', 'string', 'max:200'],
            'short_description' => ['nullable', 'string', 'max:600'],
            'description' => ['required', 'string', 'max:5000'],
            'vision' => ['required', 'string', 'max:1000'],
            'mission' => ['required', 'string', 'max:3000'],
            'address' => ['required', 'string', 'max:300'],
            'phone' => ['required', 'string', 'max:50', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'fax' => ['nullable', 'string', 'max:50', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'email' => ['required', 'email', 'max:150'],
            'operating_hours' => ['nullable', 'string', 'max:500'],
            'maps_url' => ['nullable', 'url:https', 'max:500'],
            'stats' => ['array', 'max:6'],
            'stats.*.value' => ['required', 'string', 'max:30'],
            'stats.*.label' => ['required', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'description.required' => 'Deskripsi perusahaan wajib diisi.',
            'vision.required' => 'Visi wajib diisi.',
            'mission.required' => 'Misi wajib diisi (satu poin per baris).',
            'address.required' => 'Alamat wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'fax.regex' => 'Format nomor fax tidak valid.',
            'whatsapp.required' => 'Nomor WhatsApp sales wajib diisi.',
            'whatsapp.regex' => 'Format nomor WhatsApp tidak valid.',
            'email.required' => 'Email perusahaan wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'maps_url.url' => 'Tautan Google Maps harus berupa URL https yang valid.',
            'stats.max' => 'Maksimal 6 angka statistik.',
            'stats.*.value.required' => 'Nilai statistik wajib diisi.',
            'stats.*.label.required' => 'Label statistik wajib diisi.',
        ];
    }
}
