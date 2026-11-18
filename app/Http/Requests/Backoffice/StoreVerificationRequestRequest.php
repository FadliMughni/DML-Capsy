<?php

namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('requestVerification', $this->route('inquiry'));
    }

    public function rules(): array
    {
        return [
            'request_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_notes.max' => 'Catatan untuk Gudang maksimal 1000 karakter.',
        ];
    }
}
