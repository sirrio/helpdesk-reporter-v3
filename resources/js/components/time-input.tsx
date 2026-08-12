import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';

type Props = Omit<ComponentProps<typeof Input>, 'type'>;

export function TimeInput(props: Props) {
    return (
        <Input
            {...props}
            type="text"
            inputMode="numeric"
            placeholder="HH:MM"
            pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]"
            title="Bitte eine Uhrzeit als HH:MM eingeben."
        />
    );
}
