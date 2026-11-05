<?php

namespace App\Actions;

final class SaveAction {}

namespace App\DTOs;

final class CreateOrderData {}

namespace App\Services;

use App\Actions\SaveAction;
use App\DTOs\CreateOrderData;

final class HandleService
{
    public function __construct(SaveAction $value, CreateOrderData $data) {}
}

new HandleService(value: new SaveAction(), data: new CreateOrderData());
