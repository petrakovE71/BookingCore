<?php

namespace App\Http\Requests;

use App\Models\Guide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHuntingBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tour_name' => ['required', 'string', 'max:255'],
            'hunter_name' => ['required', 'string', 'max:255'],
            'guide_id' => ['required', 'integer', 'exists:guides,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'participants_count' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Check if guide is active
                $guide = Guide::find($this->guide_id);

                if ($guide && !$guide->is_active) {
                    $validator->errors()->add(
                        'guide_id',
                        'The selected guide is not active.'
                    );
                }

                // Check if guide is available on the selected date
                if ($guide && !$guide->isAvailableOn($this->date)) {
                    $validator->errors()->add(
                        'date',
                        'The selected guide is not available on this date.'
                    );
                }
            }
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'participants_count.max' => 'Maximum 10 participants are allowed per tour.',
            'date.after_or_equal' => 'Booking date cannot be in the past.',
        ];
    }
}
