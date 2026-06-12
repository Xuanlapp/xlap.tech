<?php

namespace App\Livewire\Concerns;

use App\Services\Monitoring\TelegramErrorReporter;
use Throwable;

trait ReportsUserActionErrors
{
    /**
     * @param  array<string, mixed>  $context
     */
    protected function reportUserActionError(Throwable $exception, string $action, array $context = []): void
    {
        app(TelegramErrorReporter::class)->report($exception, [
            'action' => $action,
            'component' => static::class,
            ...$context,
        ]);
    }
}
