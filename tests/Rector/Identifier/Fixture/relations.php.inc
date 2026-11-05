<?php declare(strict_types=1);

namespace App\RectorTests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface HasVerification
{
    public function verification(): HasOne;
}

final class VerificationModel extends Model implements HasVerification
{
    public function verification(): HasOne
    {
        return $this->hasOne(VerificationRecord::class);
    }
}

final class VerificationRecord extends Model {}

final class RelationReturningService
{
    public function audits(): HasOne
    {
        throw new \LogicException();
    }
}
-----
<?php declare(strict_types=1);

namespace App\RectorTests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface HasVerification
{
    public const RelationVerification = 'verification';
    public function verification(): HasOne;
}

final class VerificationModel extends Model implements HasVerification
{
    public function verification(): HasOne
    {
        return $this->hasOne(VerificationRecord::class);
    }
}

final class VerificationRecord extends Model {}

final class RelationReturningService
{
    public function audits(): HasOne
    {
        throw new \LogicException();
    }
}
