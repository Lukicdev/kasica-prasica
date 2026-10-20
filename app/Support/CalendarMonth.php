<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * A calendar month, the same boundary the transaction list uses.
 *
 * The workspace does not store a budget start day yet, so October is the
 * first through the last of October.
 */
final readonly class CalendarMonth
{
    private function __construct(private CarbonImmutable $start) {}

    public static function current(): self
    {
        return new self(CarbonImmutable::now()->startOfMonth());
    }

    /**
     * A month from the page address. Anything else is the current month.
     */
    public static function fromQuery(string $value): self
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            return self::current();
        }

        return self::fromValue($value);
    }

    public static function fromValue(string $value): self
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $value.'-01');

        if (! $start instanceof CarbonImmutable) {
            return self::current();
        }

        return new self($start->startOfMonth());
    }

    public function value(): string
    {
        return $this->start->format('Y-m');
    }

    public function label(): string
    {
        return $this->start->format('F Y');
    }

    public function date(): string
    {
        return $this->start->toDateString();
    }

    public function start(): CarbonImmutable
    {
        return $this->start;
    }

    public function endExclusive(): CarbonImmutable
    {
        return $this->start->addMonth();
    }

    public function previous(): self
    {
        return new self($this->start->subMonth());
    }

    public function next(): self
    {
        return new self($this->start->addMonth());
    }
}
