import { cn } from '@/lib/utils';

type MoneyDirection = 'income' | 'expense';

type MoneyAmountProps = {
    formatted: string;
    originalFormatted?: string | null;
    className?: string;
    direction?: MoneyDirection | null;
};

function directed(
    formatted: string,
    direction?: MoneyDirection | null,
): string {
    if (direction !== 'income' && direction !== 'expense') {
        return formatted;
    }

    if (formatted.startsWith('-') || formatted.startsWith('+')) {
        return formatted;
    }

    return `${direction === 'income' ? '+' : '-'}${formatted}`;
}

export function moneyDirection(type: string | null): MoneyDirection | null {
    if (type === 'income' || type === 'expense') {
        return type;
    }

    return null;
}

export function MoneyAmount({
    formatted,
    originalFormatted,
    className,
    direction,
}: MoneyAmountProps) {
    return (
        <span
            className={cn(
                'inline-flex flex-wrap items-baseline gap-x-2',
                direction === 'income' && 'text-success',
                direction === 'expense' && 'text-destructive',
                className,
            )}
        >
            <span>{directed(formatted, direction)}</span>
            {originalFormatted ? (
                <span className="text-sm text-muted-foreground">
                    {directed(originalFormatted, direction)}
                </span>
            ) : null}
        </span>
    );
}
