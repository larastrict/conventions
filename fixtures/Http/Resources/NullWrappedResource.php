<?php

declare(strict_types=1);

namespace LaraStrict\ConventionsFixtures\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class NullWrappedResource extends JsonResource
{
    public static $wrap = null;
}
