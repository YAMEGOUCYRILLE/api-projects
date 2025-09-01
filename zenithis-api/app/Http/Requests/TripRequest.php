<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departure' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date|after:now',
            'arrival_date' => 'nullable|date|after:departure_date',
            'price' => 'nullable|numeric|min:0',
            'available_seats' => 'required|integer|min:1',
            'status' => 'nullable|in:active,cancelled,completed',
        ];
    }
}