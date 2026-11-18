<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\VerificationResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordVerificationResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record', $this->route('verificationRequest'));
    }

    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(VerificationResult::class)],
            'available_quantity' => ['required', 'integer', 'min:0', 'max:100000000'],
            'estimated_date' => ['nullable', 'date', 'after_or_equal:today'],
            'estimated_duration' => ['nullable', 'string', 'max:100'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Estimasi wajib (tanggal ATAU durasi) jika hasil "Tersedia Sebagian" / "Tidak Tersedia". */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $result = VerificationResult::tryFrom((string) $this->input('result'));

                if ($result?->requiresEstimate() && blank($this->input('estimated_date')) && blank($this->input('estimated_duration'))) {
                    $validator->errors()->add(
                        'estimated_date',
                        'Estimasi penyediaan (tanggal atau durasi) wajib diisi untuk hasil "'.$result->label().'".'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'result.required' => 'Hasil verifikasi wajib dipilih.',
            'result.enum' => 'Hasil verifikasi tidak valid.',
            'available_quantity.required' => 'Jumlah tersedia wajib diisi.',
            'available_quantity.integer' => 'Jumlah tersedia harus berupa bilangan bulat.',
            'available_quantity.min' => 'Jumlah tersedia tidak boleh kurang dari 0.',
            'available_quantity.max' => 'Jumlah tersedia terlalu besar.',
            'estimated_date.date' => 'Tanggal estimasi tidak valid.',
            'estimated_date.after_or_equal' => 'Tanggal estimasi tidak boleh sebelum hari ini.',
            'estimated_duration.max' => 'Durasi estimasi maksimal 100 karakter.',
            'verification_notes.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }
}
