<?php declare(strict_types=1);

namespace App\Orders\Http;

use Illuminate\Foundation\Http\FormRequest;
use Wrkflow\GetValue\GetValue;

final class GoodRequest extends FormRequest
{
    public const string KeyName = 'name';
    public const string KeyItems = 'items';

    public function rules(): array
    {
        return [
            self::KeyName => ['required', 'string'],
            self::KeyItems . '.*.' . self::KeyName => ['string'],
        ];
    }

    public function getName(GetValue $getValue): string
    {
        $this->input(self::KeyName, 'fallback');
        $this->query(key: self::KeyName, default: 'fallback');
        $getValue->getRequiredObject('stdClass', self::KeyItems, new \stdClass());
        $getValue->getRequiredObject(key: self::KeyItems, expectedClass: 'stdClass', getValueTransformer: new \stdClass());

        return $getValue->getRequiredString([self::KeyItems, self::KeyName], ['required', 'string']);
    }

    public function metadata(): array
    {
        return [
            'label' => 'not a validation key',
        ];
    }
}
