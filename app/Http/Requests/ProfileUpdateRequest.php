<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\InputSanitizationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Normalisasi profile input di server sebelum rules dijalankan.
     */
    protected function prepareForValidation(): void
    {
        $input = app(InputSanitizationService::class);

        $this->merge([
            'name' =>
                $input->singleLine(
                    $this->input('name')
                ),

            'email' =>
                $input->email(
                    $this->input('email')
                ),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)
                    ->ignore($this->user()->id),
            ],
        ];
    }
}
