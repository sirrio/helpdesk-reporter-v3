import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';

type Props = Omit<
    ComponentProps<typeof Input>,
    'type' | 'value' | 'onChange'
> & {
    value: string;
    onChange: (value: string) => void;
};

export function LocalizedDateInput({ value, onChange, ...props }: Props) {
    return (
        <Input
            {...props}
            type="date"
            title="Bitte ein Datum mit vierstelliger Jahreszahl auswählen oder eingeben."
            value={value}
            onChange={(event) => onChange(event.target.value)}
        />
    );
}
