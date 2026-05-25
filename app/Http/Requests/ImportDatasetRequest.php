<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDatasetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin authorization is handled by middleware, but we can verify auth here too.
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240', // 10 MB limit
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.file' => 'The uploaded file is invalid.',
            'csv_file.mimes' => 'The file must be a file of type: csv, txt.',
            'csv_file.max' => 'The CSV file must not be larger than 10 megabytes.',
        ];
    }
}
