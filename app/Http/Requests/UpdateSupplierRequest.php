<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Supplier;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Supplier::class) ?? false;
    }
}
