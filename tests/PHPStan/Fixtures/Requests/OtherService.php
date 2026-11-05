<?php declare(strict_types=1);

namespace App\Orders\Services;

use Wrkflow\GetValue\GetValue;

final class OtherService
{
    public function rules(): array
    {
        return [
            'name' => ['string'],
        ];
    }

    public function getName(GetValue $getValue): string
    {
        return $getValue->getRequiredString('name');
    }
}
