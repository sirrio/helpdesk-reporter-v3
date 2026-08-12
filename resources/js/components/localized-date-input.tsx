import { useEffect, useState } from 'react';
import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';

type Props = Omit<
    ComponentProps<typeof Input>,
    'type' | 'value' | 'onChange'
> & {
    value: string;
    onChange: (value: string) => void;
};

function formatDate(value: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    return match ? `${match[3]}.${match[2]}.${match[1]}` : value;
}

function parseDate(value: string): string | null {
    const isoMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    const germanMatch = /^(\d{1,2})\.(\d{1,2})\.(\d{2}|\d{4})$/.exec(
        value.trim(),
    );

    const year = isoMatch
        ? Number(isoMatch[1])
        : germanMatch
          ? Number(
                germanMatch[3].length === 2
                    ? `20${germanMatch[3]}`
                    : germanMatch[3],
            )
          : null;
    const month = isoMatch
        ? Number(isoMatch[2])
        : germanMatch
          ? Number(germanMatch[2])
          : null;
    const day = isoMatch
        ? Number(isoMatch[3])
        : germanMatch
          ? Number(germanMatch[1])
          : null;

    if (year === null || month === null || day === null) {
        return null;
    }

    const date = new Date(Date.UTC(year, month - 1, day));

    if (
        date.getUTCFullYear() !== year ||
        date.getUTCMonth() !== month - 1 ||
        date.getUTCDate() !== day
    ) {
        return null;
    }

    return `${year.toString().padStart(4, '0')}-${month
        .toString()
        .padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
}

export function LocalizedDateInput({ value, onChange, ...props }: Props) {
    const [draft, setDraft] = useState(() => formatDate(value));

    useEffect(() => {
        setDraft(formatDate(value));
    }, [value]);

    return (
        <Input
            {...props}
            type="text"
            inputMode="numeric"
            placeholder="TT.MM.JJJJ"
            pattern="(?:[0-9]{1,2}[.][0-9]{1,2}[.](?:[0-9]{2}|[0-9]{4})|[0-9]{4}-[0-9]{2}-[0-9]{2})"
            title="Bitte ein Datum als TT.MM.JJJJ eingeben. Eine zweistellige Jahreszahl ist ebenfalls möglich."
            value={draft}
            onChange={(event) => {
                const nextDraft = event.target.value;
                const parsed = parseDate(nextDraft);

                setDraft(nextDraft);

                if (nextDraft.trim() === '') {
                    onChange('');
                } else if (parsed) {
                    onChange(parsed);
                }
            }}
            onBlur={() => {
                const parsed = parseDate(draft);

                if (parsed) {
                    setDraft(formatDate(parsed));
                    onChange(parsed);
                }
            }}
        />
    );
}
