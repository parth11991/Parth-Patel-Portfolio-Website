<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'company' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter your name.',
            'name.max' => 'Name must be 120 characters or fewer.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Email must be 255 characters or fewer.',
            'subject.required' => 'Enter a subject.',
            'subject.max' => 'Subject must be 160 characters or fewer.',
            'message.required' => 'Enter a message.',
            'message.min' => 'Message must be at least 10 characters.',
            'message.max' => 'Message must be 5,000 characters or fewer.',
            'company.prohibited' => 'Your message could not be sent.',
        ];
    }
}
