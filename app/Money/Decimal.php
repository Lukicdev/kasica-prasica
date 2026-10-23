<?php

namespace App\Money;

use InvalidArgumentException;

/**
 * Exact base-10 decimal used for money. Values stay in integers, so conversion
 * does not depend on binary floating point.
 */
final class Decimal
{
    private const int MAX_COEFFICIENT_DIGITS = 15;

    private function __construct(
        private readonly int $coefficient,
        private readonly int $scale,
    ) {}

    public static function of(string $value): self
    {
        $value = trim($value);

        if (preg_match('/^-?\d+(\.\d+)?$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid decimal amount [{$value}].");
        }

        $negative = str_starts_with($value, '-');
        $absolute = ltrim($value, '-');
        $parts = explode('.', $absolute, 2);
        $whole = $parts[0];
        $fraction = rtrim($parts[1] ?? '', '0');
        $digits = ltrim($whole.$fraction, '0');

        if ($digits === '') {
            return new self(0, 0);
        }

        if (strlen($digits) > self::MAX_COEFFICIENT_DIGITS) {
            throw new InvalidArgumentException("Decimal amount [{$value}] is too large.");
        }

        $coefficient = (int) $digits;

        return new self($negative ? -$coefficient : $coefficient, strlen($fraction));
    }

    public function isZero(): bool
    {
        return $this->coefficient === 0;
    }

    public function isNegative(): bool
    {
        return $this->coefficient < 0;
    }

    public function multiply(self $other): self
    {
        return new self(
            self::multiplyInts($this->coefficient, $other->coefficient),
            $this->scale + $other->scale,
        );
    }

    public function add(self $other): self
    {
        $scale = max($this->scale, $other->scale);

        return new self(
            self::addInts($this->alignedCoefficient($scale), $other->alignedCoefficient($scale)),
            $scale,
        );
    }

    public function subtract(self $other): self
    {
        return $this->add(new self(-$other->coefficient, $other->scale));
    }

    public function divide(self $divisor, int $scale): self
    {
        if ($divisor->coefficient === 0) {
            throw new InvalidArgumentException('Cannot divide a decimal by zero.');
        }

        if ($scale < 0) {
            throw new InvalidArgumentException('Decimal scale must be zero or greater.');
        }

        $power = $scale + $divisor->scale - $this->scale;
        $numerator = $this->coefficient;
        $denominator = $divisor->coefficient;

        if ($power > 0) {
            $numerator = self::multiplyInts($numerator, self::pow10($power));
        } elseif ($power < 0) {
            $denominator = self::multiplyInts($denominator, self::pow10(-$power));
        }

        return new self(self::divRoundHalfAwayFromZero($numerator, $denominator), $scale);
    }

    public function round(int $scale): self
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Decimal scale must be zero or greater.');
        }

        if ($scale >= $this->scale) {
            return new self($this->alignedCoefficient($scale), $scale);
        }

        return new self(
            self::divRoundHalfAwayFromZero($this->coefficient, self::pow10($this->scale - $scale)),
            $scale,
        );
    }

    public function toString(int $scale): string
    {
        $rounded = $this->round($scale);
        $negative = $rounded->coefficient < 0;
        $digits = (string) abs($rounded->coefficient);

        if ($scale === 0) {
            return ($negative ? '-' : '').$digits;
        }

        $digits = str_pad($digits, $scale + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -$scale);
        $fraction = substr($digits, -$scale);
        $whole = ltrim($whole, '0');

        if ($whole === '') {
            $whole = '0';
        }

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }

    private function alignedCoefficient(int $scale): int
    {
        $power = $scale - $this->scale;

        if ($power === 0) {
            return $this->coefficient;
        }

        return self::multiplyInts($this->coefficient, self::pow10($power));
    }

    private static function pow10(int $power): int
    {
        if ($power < 0 || $power > 18) {
            throw new InvalidArgumentException('Decimal scale is out of range.');
        }

        $result = 1;

        for ($i = 0; $i < $power; $i++) {
            $result *= 10;
        }

        return $result;
    }

    private static function multiplyInts(int $left, int $right): int
    {
        if ($left !== 0 && $right !== 0 && intdiv(PHP_INT_MAX, abs($right)) < abs($left)) {
            throw new InvalidArgumentException('Decimal multiplication overflowed.');
        }

        return $left * $right;
    }

    private static function addInts(int $left, int $right): int
    {
        return $left + $right;
    }

    /**
     * Integer division that rounds halves away from zero.
     */
    private static function divRoundHalfAwayFromZero(int $numerator, int $denominator): int
    {
        $negative = ($numerator < 0) !== ($denominator < 0);
        $numerator = abs($numerator);
        $denominator = abs($denominator);
        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;
        $half = intdiv($denominator, 2) + ($denominator % 2);

        if ($remainder >= $half) {
            $quotient++;
        }

        return $negative ? -$quotient : $quotient;
    }
}
