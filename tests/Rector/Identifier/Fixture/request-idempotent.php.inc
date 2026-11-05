<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

use Illuminate\Foundation\Http\FormRequest;

final class StableRequest extends FormRequest
{
    public const KeyCode = 'code';

    public function rules(): array
    {
        return [self::KeyCode => ['string']];
    }

    public function value(): void
    {
        $this->input(self::KeyCode);
    }
}
