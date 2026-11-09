import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AssetController from '@/actions/App/Http/Controllers/AssetController';
import { DatePicker } from '@/components/date-picker';
import { FormSelect } from '@/components/form-select';
import { IconPicker } from '@/components/icon-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { assetTypeIcons } from '@/lib/icons';
import type { AssetFormValues, AssetTypeOption } from '@/types/asset';

export function AssetForm({
    asset,
    types,
    today,
}: {
    asset?: AssetFormValues;
    types: AssetTypeOption[];
    today?: string;
}) {
    const { currencies } = usePage().props;
    const editing = asset !== undefined;
    const [type, setType] = useState(asset?.type ?? 'real_estate');
    const [currency, setCurrency] = useState(
        asset?.currency ?? currencies.base,
    );
    const [icon, setIcon] = useState<string | null>(
        asset?.icon ?? assetTypeIcons[asset?.type ?? 'real_estate'] ?? null,
    );
    const [iconChosen, setIconChosen] = useState(asset?.icon != null);
    const [valuedOn, setValuedOn] = useState(asset?.valued_on ?? today ?? '');

    return (
        <Form
            {...(editing
                ? AssetController.update.form(asset.id)
                : AssetController.store.form())}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            defaultValue={asset?.name ?? ''}
                            placeholder="Family home"
                            className="h-11 md:h-9"
                            data-test="asset-name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <IconPicker
                        id="icon"
                        value={icon}
                        onChange={(next) => {
                            setIconChosen(true);
                            setIcon(next);
                        }}
                    />
                    <InputError message={errors.icon} />

                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <FormSelect
                            id="type"
                            name="type"
                            required
                            value={type}
                            invalid={Boolean(errors.type)}
                            onValueChange={(next) => {
                                setType(next);

                                if (!iconChosen) {
                                    setIcon(assetTypeIcons[next] ?? null);
                                }
                            }}
                            options={types.map((option) => ({
                                value: option.value,
                                label: option.label,
                            }))}
                        />
                        <InputError message={errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <FormSelect
                            id="currency"
                            name="currency"
                            required
                            value={currency}
                            onValueChange={setCurrency}
                            invalid={Boolean(errors.currency)}
                            options={currencies.supported.map((option) => ({
                                value: option.code,
                                label: `${option.code} · ${option.name}`,
                            }))}
                        />
                        <InputError message={errors.currency} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="value">Value</Label>
                        <Input
                            id="value"
                            name="value"
                            inputMode="decimal"
                            required
                            defaultValue={asset?.value ?? ''}
                            placeholder="0.00"
                            className="h-11 md:h-9"
                            data-test="asset-value"
                        />
                        <p className="text-sm text-muted-foreground">
                            Enter what it is worth. This value is set by hand.
                        </p>
                        <InputError message={errors.value} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="valued_on">Valued on</Label>
                        <DatePicker
                            id="valued_on"
                            name="valued_on"
                            required
                            value={valuedOn}
                            onChange={setValuedOn}
                        />
                        <InputError message={errors.valued_on} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="note">Note</Label>
                        <Textarea
                            id="note"
                            name="note"
                            defaultValue={asset?.note ?? ''}
                            rows={3}
                            placeholder="Optional"
                            aria-invalid={errors.note ? true : undefined}
                        />
                        <InputError message={errors.note} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="h-11 w-full sm:w-auto md:h-9"
                        data-test="save-asset"
                    >
                        {editing ? 'Save changes' : 'Create asset'}
                    </Button>
                </>
            )}
        </Form>
    );
}
