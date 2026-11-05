<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public const KeyConflict = 'other';

    public function rules(): array
    {
        return ['name' => ['required'], 'unmapped' => ['string'], '*' => ['string']];
    }

    public function values(): void
    {
        $this->input('name');
        $this->input(default: null, key: 'name');
        $this->header('X-Api-Key');
        $this->input('*');
        $this->input('conflict');
        $callback = $this->input(...);
    }
}
-----
<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public const KeyConflict = 'other';
    public const KeyName = 'name';
    public const KeyUnmapped = 'unmapped';
    public const KeyXApiKey = 'X-Api-Key';

    public function rules(): array
    {
        return [self::KeyName => ['required'], self::KeyUnmapped => ['string'], '*' => ['string']];
    }

    public function values(): void
    {
        $this->input(self::KeyName);
        $this->input(default: null, key: self::KeyName);
        $this->header(self::KeyXApiKey);
        $this->input('*');
        $this->input('conflict');
        $callback = $this->input(...);
    }
}
