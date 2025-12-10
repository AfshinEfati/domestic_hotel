<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ProviderDTO
{
    public mixed $id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $code;
    public mixed $config;
    public mixed $is_active;
    public mixed $created_at;
    public mixed $updated_at;
    public mixed $auth_token;
    public mixed $expire_at;

    public function __construct(
        mixed $id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $code = null,
        mixed $config = null,
        mixed $is_active = null,
        mixed $created_at = null,
        mixed $updated_at = null,
        mixed $auth_token = null,
        mixed $expire_at = null
    ) {
        $this->id = $id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->code = $code;
        $this->config = $config;
        $this->is_active = $is_active;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->auth_token = $auth_token;
        $this->expire_at = $expire_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->fa_name = $request->input('fa_name');
        $dto->en_name = $request->input('en_name');
        $dto->code = $request->input('code');
        $dto->config = $request->input('config');
        $dto->is_active = $request->input('is_active');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');
        $dto->auth_token = $request->input('auth_token');
        $dto->expire_at = $request->input('expire_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->code !== null) { $out['code'] = $this->code; }
        if ($this->config !== null) { $out['config'] = $this->config; }
        if ($this->is_active !== null) { $out['is_active'] = $this->is_active; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }
        if ($this->auth_token !== null) { $out['auth_token'] = $this->auth_token; }
        if ($this->expire_at !== null) { $out['expire_at'] = $this->expire_at; }

        return $out;
    }
}
