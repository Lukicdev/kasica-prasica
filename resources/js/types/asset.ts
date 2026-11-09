export type MoneyFigure = {
    amount: string;
    currency: string;
    formatted: string;
    negative: boolean;
};

export type AssetSummary = {
    id: number;
    name: string;
    icon: string | null;
    type: string;
    type_label: string;
    currency: string;
    current_value: MoneyFigure;
    display_value: MoneyFigure | null;
    valued_on_label: string;
    valuation_label: string;
    archived: boolean;
    note: string | null;
};

export type AssetGroup = {
    type: string;
    label: string;
    assets: AssetSummary[];
};

export type AssetTypeOption = {
    value: string;
    label: string;
};

export type AssetFormValues = {
    id: number;
    name: string;
    icon: string | null;
    type: string;
    currency: string;
    value: string;
    valued_on: string;
    valuation: string;
    note: string | null;
    archived: boolean;
};

export type AssetDetail = AssetFormValues & {
    type_label: string;
    valued_on_label: string;
    valuation_label: string;
    current_value: MoneyFigure;
    display_value: MoneyFigure | null;
    updated_by: string | null;
};
