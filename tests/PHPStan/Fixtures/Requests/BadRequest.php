<?php declare(strict_types=1);

namespace App\Orders\Http;

use Illuminate\Foundation\Http\FormRequest;
use Wrkflow\GetValue\GetValue;

final class BadRequest extends FormRequest
{
    public const string KeyItems = 'items';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            self::KeyItems . '.*.label' => ['string'],
        ];
    }

    public function getName(GetValue $getValue): string
    {
        $this->input('name', 'fallback');
        $this->query(key: 'page', default: 'fallback');
        $getValue->getRequiredObject('stdClass', 'items', new \stdClass());
        $getValue->getRequiredObject(key: 'items', expectedClass: 'stdClass', getValueTransformer: new \stdClass());

        return $getValue->getRequiredString([self::KeyItems, 'name'], ['required', 'string']);
    }
}
