import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const emptyValue = '__empty__';

export type FormSelectOption = {
    value: string;
    label: string;
};

export function FormSelect({
    id,
    name,
    value,
    defaultValue = '',
    onValueChange,
    options,
    placeholder,
    emptyLabel,
    disabled = false,
    required = false,
    invalid = false,
    className,
    'data-test': dataTest,
}: {
    id: string;
    name?: string;
    value?: string;
    defaultValue?: string;
    onValueChange?: (value: string) => void;
    options: FormSelectOption[];
    placeholder?: string;
    emptyLabel?: string;
    disabled?: boolean;
    required?: boolean;
    invalid?: boolean;
    className?: string;
    'data-test'?: string;
}) {
    const [uncontrolled, setUncontrolled] = useState(defaultValue);
    const current = value ?? uncontrolled;
    const displayValue = current === '' && emptyLabel ? emptyValue : current;

    const selectValue = (next: string): void => {
        const resolved = next === emptyValue ? '' : next;

        if (value === undefined) {
            setUncontrolled(resolved);
        }

        onValueChange?.(resolved);
    };

    return (
        <>
            {name ? <input type="hidden" name={name} value={current} /> : null}
            <Select
                value={displayValue}
                onValueChange={selectValue}
                disabled={disabled}
            >
                <SelectTrigger
                    id={id}
                    aria-required={required || undefined}
                    aria-invalid={invalid || undefined}
                    data-test={dataTest}
                    className={cn(
                        'h-11 w-full data-[size=default]:h-11 md:h-9 md:data-[size=default]:h-9',
                        className,
                    )}
                >
                    <SelectValue placeholder={placeholder ?? emptyLabel} />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        {emptyLabel ? (
                            <SelectItem value={emptyValue}>
                                {emptyLabel}
                            </SelectItem>
                        ) : null}
                        {options.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectGroup>
                </SelectContent>
            </Select>
        </>
    );
}
