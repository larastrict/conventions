<?php

namespace App\Payments\Capabilities\Gateway;

final class Factory {}

namespace App\Billing\Queries;

final class GenericCollaboratorQuery
{
    public function execute(): mixed { return app(\App\Payments\Capabilities\Gateway\Factory::class); }
}
-----
<?php

namespace App\Payments\Capabilities\Gateway;

final class Factory {}

namespace App\Billing\Queries;

final class GenericCollaboratorQuery
{
    public function execute(): mixed { return $this->gatewayFactory; }
    public function __construct(private \App\Payments\Capabilities\Gateway\Factory $gatewayFactory)
    {
    }
}
