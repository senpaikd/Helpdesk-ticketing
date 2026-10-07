<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attach', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'attachment' => ['required', File::types(['jpg', 'jpeg', 'png', 'pdf', 'txt', 'doc', 'docx'])->max(5 * 1024)],
        ];
    }
}
