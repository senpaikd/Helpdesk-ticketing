<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updatePriority', $this->route('ticket'));
    }

    public function rules(): array
    {
        return ['priority' => ['required', Rule::enum(TicketPriority::class)]];
    }
}
