import { format, isValid, parse } from 'date-fns';
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { useState, type ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const monthIndexes = Array.from({ length: 12 }, (_, month) => month);

function parseMonth(value: string): Date | undefined {
    const date = parse(value, 'yyyy-MM', new Date());

    return isValid(date) ? date : undefined;
}

function isSameMonth(date: Date | undefined, other: Date): boolean {
    return (
        date !== undefined &&
        date.getFullYear() === other.getFullYear() &&
        date.getMonth() === other.getMonth()
    );
}

export function MonthPicker({
    id,
    name,
    value,
    onChange,
    className,
    placeholder = 'Pick a month',
    icon = true,
    variant = 'outline',
    align = 'start',
}: {
    id: string;
    name: string;
    value: string;
    onChange?: (value: string) => void;
    className?: string;
    placeholder?: string;
    icon?: boolean;
    variant?: ComponentProps<typeof Button>['variant'];
    align?: ComponentProps<typeof PopoverContent>['align'];
}) {
    const selectedFromValue = parseMonth(value);
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(value);
    const [source, setSource] = useState(value);
    const [year, setYear] = useState(
        () => selectedFromValue?.getFullYear() ?? new Date().getFullYear(),
    );

    if (value !== source) {
        setSource(value);
        setDraft(value);

        if (selectedFromValue) {
            setYear(selectedFromValue.getFullYear());
        }
    }

    const selected = parseMonth(draft);
    const now = new Date();
    const selectedIsCurrent = isSameMonth(selected, now);

    function choose(month: number) {
        const next = format(new Date(year, month, 1), 'yyyy-MM');

        setDraft(next);
        onChange?.(next);
        setOpen(false);
    }

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (next && selected) {
                    setYear(selected.getFullYear());
                }
            }}
        >
            <input type="hidden" name={name} value={draft} />
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant={variant}
                    data-empty={!selected}
                    aria-current={selectedIsCurrent ? 'date' : undefined}
                    className={cn(
                        icon
                            ? 'h-11 w-full justify-start px-3 text-left font-normal md:h-9'
                            : 'h-9 w-auto justify-center px-3 font-normal',
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    {icon ? <CalendarIcon /> : null}
                    {selected ? (
                        <span className="inline-flex items-center gap-2">
                            {format(selected, 'MMMM yyyy')}
                            {selectedIsCurrent ? (
                                <span
                                    aria-hidden
                                    className="size-1.5 rounded-full bg-primary"
                                />
                            ) : null}
                        </span>
                    ) : (
                        <span>{placeholder}</span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-3" align={align}>
                <div className="flex items-center justify-between gap-2 pb-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Previous year"
                        onClick={() => setYear((current) => current - 1)}
                    >
                        <ChevronLeftIcon />
                    </Button>
                    <span className="text-sm font-medium">{year}</span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Next year"
                        onClick={() => setYear((current) => current + 1)}
                    >
                        <ChevronRightIcon />
                    </Button>
                </div>
                <div className="grid grid-cols-3 gap-2">
                    {monthIndexes.map((month) => {
                        const active =
                            selected?.getFullYear() === year &&
                            selected.getMonth() === month;
                        const current =
                            now.getFullYear() === year &&
                            now.getMonth() === month;

                        return (
                            <Button
                                key={month}
                                type="button"
                                variant={
                                    active
                                        ? 'default'
                                        : current
                                          ? 'secondary'
                                          : 'ghost'
                                }
                                aria-current={current ? 'date' : undefined}
                                className="relative h-9 px-2 font-normal"
                                onClick={() => choose(month)}
                            >
                                {format(new Date(year, month, 1), 'MMM')}
                                {current ? (
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'absolute bottom-1 left-1/2 size-1 -translate-x-1/2 rounded-full',
                                            active
                                                ? 'bg-primary-foreground'
                                                : 'bg-primary',
                                        )}
                                    />
                                ) : null}
                            </Button>
                        );
                    })}
                </div>
            </PopoverContent>
        </Popover>
    );
}
