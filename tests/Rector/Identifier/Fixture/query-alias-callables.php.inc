<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

final class QueryAliasCallables
{
    public function callbacks(\Illuminate\Database\Eloquent\Builder $query): array
    {
        return [
            $this->transform(...),
            $query->value(...),
            $query->selectRaw(...),
            $query->selectRaw('COUNT(*) as record_count')->value(...),
        ];
    }

    private function transform(mixed $value): mixed
    {
        return $value;
    }
}
