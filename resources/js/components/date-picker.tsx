import { format, isValid, parse } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

function parseDate(value: string): Date | undefined {
    const date = parse(value, 'yyyy-MM-dd', new Date());

    return isValid(date) ? date : undefined;
}

export function DatePicker({
    id,
    name,
    value,
    onChange,
    required = false,
    min,
    max,
    className,
    placeholder = 'Pick a date',
}: {
    id: string;
    name: string;
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
    min?: string;
    max?: string;
    className?: string;
    placeholder?: string;
}) {
    const [open, setOpen] = useState(false);
    const selected = parseDate(value);
    const minDate = min ? parseDate(min) : undefined;
    const maxDate = max ? parseDate(max) : undefined;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <input type="hidden" name={name} value={value} />
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    data-empty={!selected}
                    aria-required={required}
                    className={cn(
                        'h-11 w-full justify-start px-3 text-left font-normal md:h-9',
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon />
                    {selected ? (
                        format(selected, 'PPP')
                    ) : (
                        <span>{placeholder}</span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    required={required}
                    selected={selected}
                    defaultMonth={selected ?? minDate}
                    startMonth={minDate}
                    endMonth={maxDate}
                    disabled={[
                        ...(minDate ? [{ before: minDate }] : []),
                        ...(maxDate ? [{ after: maxDate }] : []),
                    ]}
                    onSelect={(date: Date | undefined) => {
                        if (!date) {
                            return;
                        }

                        onChange(format(date, 'yyyy-MM-dd'));
                        setOpen(false);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}
