<?php

namespace App\Queries;

use App\Support\CalendarMonth;
use Illuminate\Http\Request;

/**
 * The months chosen on the insights page.
 */
final readonly class InsightsQuery
{
    public function __construct(
        public CalendarMonth $left,
        public CalendarMonth $right,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $rightInput = $request->string('right')->toString();
        $right = $rightInput === ''
            ? CalendarMonth::current()
            : CalendarMonth::fromQuery($rightInput);

        $leftInput = $request->string('left')->toString();
        $left = $leftInput === ''
            ? $right->previous()
            : CalendarMonth::fromQuery($leftInput);

        return new self(
            left: $left,
            right: $right,
        );
    }
}
