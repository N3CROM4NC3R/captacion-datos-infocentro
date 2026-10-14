<?php

namespace App\Enums\Concerns;

trait ProvidesOptions
{
    /**
     * Get the cases as an associative array of value => label suitable for a select input.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
