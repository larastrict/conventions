<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

final class VerificationStats
{
    private const AliasConflict = 'unrelated';

    public function query(\Illuminate\Database\Eloquent\Builder $query, string $table): mixed
    {
        $value = $query->selectRaw("MAX({$table}.created_at) as last_verification_at", ['bound'])->value('last_verification_at');
        $other = $query->selectRaw('COUNT(*) as record_count', [])->value('record_count');
        $conflict = $query->selectRaw('COUNT(*) as conflict', [])->value('conflict');

        return [$value, $other, $conflict];
    }
}
-----
<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

final class VerificationStats
{
    private const AliasLastVerificationAt = 'last_verification_at', AliasRecordCount = 'record_count';
    private const AliasConflict = 'unrelated';

    public function query(\Illuminate\Database\Eloquent\Builder $query, string $table): mixed
    {
        $value = $query->selectRaw("MAX({$table}.created_at) as " . self::AliasLastVerificationAt, ['bound'])->value(self::AliasLastVerificationAt);
        $other = $query->selectRaw('COUNT(*) as ' . self::AliasRecordCount, [])->value(self::AliasRecordCount);
        $conflict = $query->selectRaw('COUNT(*) as conflict', [])->value('conflict');

        return [$value, $other, $conflict];
    }
}
