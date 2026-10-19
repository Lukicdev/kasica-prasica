import { cn } from '@/lib/utils';

type MoneyAmountProps = {
    formatted: string;
    originalFormatted?: string | null;
    className?: string;
};

export function MoneyAmount({
    formatted,
    originalFormatted,
    className,
}: MoneyAmountProps) {
    return (
        <span
            className={cn(
                'inline-flex flex-wrap items-baseline gap-x-2',
                className,
            )}
        >
            <span>{formatted}</span>
            {originalFormatted ? (
                <span className="text-sm text-muted-foreground">
                    {originalFormatted}
                </span>
            ) : null}
        </span>
    );
}
