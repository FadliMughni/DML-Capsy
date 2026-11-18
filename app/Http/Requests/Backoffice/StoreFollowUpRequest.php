<?php

namespace App\Http\Requests\Backoffice;

use App\Models\InquiryFollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addFollowUp', $this->route('inquiry'));
    }

    public function rules(): array
    {
        return [
            'contacted_at' => ['nullable', 'date', 'before_or_equal:now'],
            'channel' => ['required', Rule::in(InquiryFollowUp::CHANNELS)],
            'notes' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contacted_at.date' => 'Waktu komunikasi tidak valid.',
            'contacted_at.before_or_equal' => 'Waktu komunikasi tidak boleh di masa depan.',
            'channel.required' => 'Media komunikasi wajib dipilih.',
            'channel.in' => 'Media komunikasi tidak valid.',
            'notes.required' => 'Catatan tindak lanjut wajib diisi.',
            'notes.min' => 'Catatan tindak lanjut minimal 3 karakter.',
            'notes.max' => 'Catatan tindak lanjut maksimal 2000 karakter.',
        ];
    }
}
