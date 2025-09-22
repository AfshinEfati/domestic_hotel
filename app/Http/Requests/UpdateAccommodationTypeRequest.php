<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccommodationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'id' => 'sometimes|nullable',
            'fa_name' => 'sometimes|nullable',
            'en_name' => 'sometimes|nullable',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];

        // Convert 'unique:table,field' to Rule::unique(...)->ignore($id)
        foreach ($rules as $field => &$pipe) {
            if (!is_string($pipe)) {
                continue;
            }
            $parts = explode('|', $pipe);
            foreach ($parts as &$p) {
                if (strpos($p, 'unique:') === 0) {
                    $p2 = substr($p, 7);
                    $tmp = explode(',', $p2, 2);
                    $tbl = $tmp[0] !== '' ? $tmp[0] : 'accommodation_types';
                    $col = isset($tmp[1]) && $tmp[1] !== '' ? $tmp[1] : $field;

                    $id = null;
                    $routeParam = $this->route('accommodationType');
                    if ($routeParam) {
                        if (is_object($routeParam) && method_exists($routeParam, 'getKey')) {
                            $id = $routeParam->getKey();
                        } elseif (is_numeric($routeParam)) {
                            $id = (int) $routeParam;
                        }
                    }

                    $p = Rule::unique($tbl, $col)->ignore($id);
                }
            }
            unset($p);
            $pipe = $parts;
        }
        unset($pipe);

        foreach ($rules as $k => &$arr) {
            if (is_array($arr)) {
                $arr = array_map(function ($x) { return $x; }, $arr);
            }
        }
        unset($arr);

        return $rules;
    }
}
