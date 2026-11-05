<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Owner extends Model
{
    protected $hidden = ['remember_token'];

    public function records(): HasMany
    {
        return $this->hasMany(self::class);
    }
}
-----
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Owner extends Model
{
    public const AttributeRememberToken = 'remember_token';
    protected $hidden = [self::AttributeRememberToken];
    public const RelationRecords = 'records';

    public function records(): HasMany
    {
        return $this->hasMany(self::class);
    }
}
