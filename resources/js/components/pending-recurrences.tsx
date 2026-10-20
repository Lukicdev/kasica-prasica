import { Form, Link } from '@inertiajs/react';
import RecurrenceController from '@/actions/App/Http/Controllers/RecurrenceController';
import { MoneyAmount } from '@/components/money-amount';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/recurrences';
import type { PendingRecurrence } from '@/types/recurrence';

export function PendingRecurrences({ items }: { items: PendingRecurrence[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <section
            aria-label="Pending recurring transactions"
            className="flex flex-col gap-3"
            data-test="pending-recurrences"
        >
            <div className="flex flex-col gap-1">
                <h2 className="text-lg font-semibold tracking-tight">
                    To confirm
                </h2>
                <p className="text-sm text-muted-foreground">
                    These repeat every month. They stay pending until you
                    confirm them, and they are left out of spending and account
                    balances until then.
                </p>
            </div>
            <ul className="flex flex-col gap-3">
                {items.map((item) => (
                    <li
                        key={item.id}
                        className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        data-test="pending-recurrence"
                    >
                        <div className="flex flex-col gap-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    data-test="pending-status"
                                >
                                    {item.status_label}
                                </Badge>
                                <span className="text-sm text-muted-foreground">
                                    {item.type_label}
                                </span>
                            </div>
                            <p className="font-medium">
                                {item.category ?? item.type_label}
                            </p>
                            <p className="text-xl font-semibold tracking-tight">
                                <MoneyAmount
                                    formatted={item.amount.formatted}
                                    originalFormatted={
                                        item.amount.original_formatted
                                    }
                                />
                            </p>
                            <p className="text-sm text-muted-foreground">
                                <time dateTime={item.date}>
                                    {item.date_label}
                                </time>
                                {' · '}
                                {item.account}
                                {item.person ? ` · ${item.person}` : ''}
                            </p>
                            {item.note ? (
                                <p className="text-sm">{item.note}</p>
                            ) : null}
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Form
                                {...RecurrenceController.store.form(item.id)}
                                className="sm:flex-1"
                            >
                                <input
                                    type="hidden"
                                    name="month"
                                    value={item.month}
                                />
                                <Button
                                    type="submit"
                                    className="h-11 w-full"
                                    aria-label={`Confirm ${item.category ?? item.type_label} for ${item.date_label}`}
                                    data-test="confirm-recurrence"
                                >
                                    Confirm
                                </Button>
                            </Form>
                            <Button
                                asChild
                                variant="outline"
                                className="h-11 sm:flex-1"
                            >
                                <Link
                                    href={edit(item.id, {
                                        query: { month: item.month },
                                    })}
                                    aria-label={`Adjust ${item.category ?? item.type_label} for ${item.date_label}`}
                                    data-test="adjust-recurrence"
                                >
                                    Adjust
                                </Link>
                            </Button>
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}
