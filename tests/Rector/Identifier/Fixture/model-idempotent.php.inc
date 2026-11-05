<?php declare(strict_types=1);

namespace App\RectorTests\Identifier;

use Illuminate\Database\Eloquent\Model;

final class StableRecord extends Model
{
    public const AttributeCode = 'code';
    protected $fillable = [self::AttributeCode];

    public function search(): void
    {
        $this->where(self::AttributeCode);
    }
}
