<?php

namespace App\Support;

use App\Models\Transaction;
use Carbon\CarbonImmutable;

/**
 * A repeating transaction is due once a month, on its own day or the last day
 * of a shorter month. The month it was entered already has the real entry.
 */
class RecurringSchedule
{
    public function isDue(Transaction $template, CalendarMonth $month): bool
    {
        if (! $template->repeats || ! $template->type->isIncomeOrExpense()) {
            return false;
        }

        return $template->booked_on->toDateString() < $month->start()->toDateString();
    }

    public function date(Transaction $template, CalendarMonth $month): CarbonImmutable
    {
        $start = $month->start();
        $day = min($template->booked_on->day, $start->daysInMonth);

        return $start->setDay($day);
    }

    public function occurrence(Transaction $template, CalendarMonth $month): ?Transaction
    {
        $found = $template->occurrences()
            ->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString())
            ->orderBy('id')
            ->first();

        return $found instanceof Transaction ? $found : null;
    }
}
