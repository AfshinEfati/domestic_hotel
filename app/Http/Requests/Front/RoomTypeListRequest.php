<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from'=>['nullable','integer'],
            'to'=>['nullable','integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
