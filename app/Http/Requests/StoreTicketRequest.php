<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'attachment' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf', 'txt', 'doc', 'docx'])->max(5 * 1024)],
        ];
    }
}
