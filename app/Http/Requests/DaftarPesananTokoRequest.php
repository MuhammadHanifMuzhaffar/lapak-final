<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Pesanan\StatusPesanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DaftarPesananTokoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(StatusPesanan::class)],
            'per_halaman' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
