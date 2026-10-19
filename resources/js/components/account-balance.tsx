import { Badge } from '@/components/ui/badge';
import type { MoneyFigure } from '@/types/account';

export function AccountBalance({
    balance,
    className,
}: {
    balance: MoneyFigure;
    className?: string;
}) {
    return (
        <span className={className}>
            <span className="inline-flex flex-wrap items-center gap-2">
                <span data-test="account-balance">{balance.formatted}</span>
                {balance.negative ? (
                    <Badge variant="outline" data-test="negative-balance">
                        Negative
                    </Badge>
                ) : null}
            </span>
        </span>
    );
}
