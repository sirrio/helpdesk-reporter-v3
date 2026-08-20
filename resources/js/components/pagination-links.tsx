import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type PaginationLink = { url: string | null; label: string; active: boolean };

function decodePaginationLabel(label: string): string {
    return label
        .replace('&laquo; Previous', 'Zurück')
        .replace('Next &raquo;', 'Weiter');
}

export function PaginationLinks({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2 pt-2">
            {links.map((link, index) => (
                <Button
                    key={`${link.label}-${index}`}
                    type="button"
                    variant={link.active ? 'default' : 'outline'}
                    disabled={link.url === null}
                    asChild={link.url !== null}
                >
                    {link.url ? (
                        <Link href={link.url}>
                            {decodePaginationLabel(link.label)}
                        </Link>
                    ) : (
                        <span>{decodePaginationLabel(link.label)}</span>
                    )}
                </Button>
            ))}
        </div>
    );
}
