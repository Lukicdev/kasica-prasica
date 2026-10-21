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
import { Toggle } from '@/components/ui/toggle';
import { iconComponent, iconLabel, iconOptions } from '@/lib/icons';

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
                        className="h-11 w-full justify-start sm:w-auto md:h-9"
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
                        <Toggle
                            type="button"
                            variant="outline"
                            pressed={value === null}
                            data-test="icon-none"
                            className="h-11 px-4 md:h-9"
                            onPressedChange={() => choose(null)}
                        >
                            No icon
                        </Toggle>
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
                                    <Toggle
                                        key={option.name}
                                        type="button"
                                        variant="outline"
                                        pressed={value === option.name}
                                        aria-label={option.label}
                                        title={option.label}
                                        data-test="icon-option"
                                        className="size-11"
                                        onPressedChange={() =>
                                            choose(option.name)
                                        }
                                    >
                                        {Icon ? <Icon /> : null}
                                    </Toggle>
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
