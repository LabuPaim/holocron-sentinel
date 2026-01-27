<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Verifica se o usuário está autorizado a fazer esta solicitação.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à solicitação.
     *
     * @return array<string, string|array>
     */
    public function rules(): array
    {
        return [
            'external_id' => ['required', 'string', 'min:1', 'max:255'],
            'type' => ['required', 'string', Rule::in(['info', 'warning', 'critical'])],
            'payload' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('type')) {
            $this->merge([
                'type' => strtolower(trim($this->input('type'))),
            ]);
        }
    }
}
