<?php

namespace App\Verification\Models\Scopes;

class WhereStateIn {}

function useScope(WhereStateIn $scope): WhereStateIn
{
    $new = new WhereStateIn();
    $class = WhereStateIn::class;
    $string = 'App\Verification\Models\Scopes\WhereStateIn';
    return $scope;
}
-----
<?php

namespace App\Verification\Models\Scopes;

class WhereStateInScope {}

function useScope(\App\Verification\Models\Scopes\WhereStateInScope $scope): \App\Verification\Models\Scopes\WhereStateInScope
{
    $new = new \App\Verification\Models\Scopes\WhereStateInScope();
    $class = \App\Verification\Models\Scopes\WhereStateInScope::class;
    $string = 'App\Verification\Models\Scopes\WhereStateInScope';
    return $scope;
}
