<?php declare(strict_types=1);

namespace App\Ui\Resources;

use Carbon\Factory as CarbonFactory;
use Filament\Actions\Action;
use Filament\Resources\Resource;

final class CallbackResource extends Resource
{
    public static function schema(): void
    {
        new Action()
            ->url(static function (): string {
                app(CarbonFactory::class);
                now();
                \Carbon\Carbon::parse('2026-01-01');
                new \DateTimeImmutable();

                return '/';
            });
    }
}
