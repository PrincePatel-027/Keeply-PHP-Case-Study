<?php

namespace App\Http\Requests;

use App\Models\Item;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && $this->user()?->can('view', $item);
    }

    public function rules(): array
    {
        return [
            'purpose' => ['required', Rule::in(['receipt', 'warranty', 'manual', 'product_photo', 'other'])],
            'document' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath());
                    $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

                    if (! in_array($mimeType, $allowed, true)) {
                        $fail('The file contents must be a PDF, JPG, PNG, or WebP document.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'document' => 'file',
        ];
    }
}
