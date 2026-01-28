<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntityRequest extends FormRequest
{
    /**
     * Verifica se o usuário está autorizado a fazer esta solicitação.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza os dados antes da validação.
     * Name em minúsculas para idempotência case-insensitive ("Foo" e "foo" = mesma entidade).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strtolower(trim($this->input('name'))),
            ]);
        }
    }

    /**
     * Obtém as regras de validação que se aplicam à solicitação.
     *
     * @return array<string, string|array>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    /**
     * Mensagens de erro customizadas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome da entidade é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo :min caracteres.',
            'name.max' => 'O nome deve ter no máximo :max caracteres.',
        ];
    }
}
