import { useState } from 'react';
import { EntityIcon } from '@/components/entity-icon';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { iconComponent, iconLabel, iconOptions } from '@/lib/icons';
import { cn } from '@/lib/utils';

export function IconPicker({
    id,
    name = 'icon',
    value,
    onChange,
}: {
    id: string;
    name?: string;
    value: string | null;
    onChange: (value: string | null) => void;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = iconLabel(value);
    const normalizedQuery = query.trim().toLowerCase();
    const options = iconOptions.filter((option) =>
        option.label.toLowerCase().includes(normalizedQuery),
    );

    const choose = (next: string | null): void => {
        onChange(next);
        setOpen(false);
        setQuery('');
    };

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>Icon</Label>
            <input type="hidden" name={name} value={value ?? ''} />
            <Dialog
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);

                    if (!next) {
                        setQuery('');
                    }
                }}
            >
                <DialogTrigger asChild>
                    <Button
                        type="button"
                        id={id}
                        variant="outline"
                        className="h-11 w-full justify-start sm:w-auto"
                        aria-label={
                            selected ? `Icon, ${selected}` : 'Choose an icon'
                        }
                        data-test="icon-picker"
                    >
                        <EntityIcon name={value} />
                        {selected ?? 'Choose an icon'}
                    </Button>
                </DialogTrigger>
                <DialogContent className="max-h-[min(36rem,calc(100%-2rem))] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Choose an icon</DialogTitle>
                        <DialogDescription>
                            Pick one from the list, or search by name.
                        </DialogDescription>
                    </DialogHeader>
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search icons"
                        aria-label="Search icons"
                        className="h-11 md:h-9"
                        data-test="icon-search"
                    />
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            aria-pressed={value === null}
                            data-test="icon-none"
                            className={cn(
                                'inline-flex h-11 items-center justify-center rounded-md border border-input px-4 text-sm font-medium text-foreground hover:bg-accent',
                                value === null && 'border-ring bg-accent',
                            )}
                            onClick={() => choose(null)}
                        >
                            No icon
                        </button>
                    </div>
                    {options.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No icons match that search.
                        </p>
                    ) : (
                        <div className="grid grid-cols-4 gap-2 sm:grid-cols-6">
                            {options.map((option) => {
                                const Icon = iconComponent(option.name);

                                return (
                                    <button
                                        key={option.name}
                                        type="button"
                                        aria-label={option.label}
                                        aria-pressed={value === option.name}
                                        title={option.label}
                                        data-test="icon-option"
                                        className={cn(
                                            'flex size-11 items-center justify-center rounded-md border border-input text-foreground hover:bg-accent',
                                            value === option.name &&
                                                'border-ring bg-accent',
                                        )}
                                        onClick={() => choose(option.name)}
                                    >
                                        {Icon ? (
                                            <Icon className="size-4" />
                                        ) : null}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
            <p className="text-sm text-muted-foreground">
                Shown next to the name.
            </p>
        </div>
    );
}
