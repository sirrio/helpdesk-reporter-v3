import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Archive,
    CalendarDays,
    CalendarRange,
    PencilLine,
    Plus,
    RotateCcw,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    destroy as destroySemester,
    index as adminSemestersIndex,
    restore as restoreSemester,
    store as storeSemester,
    update as updateSemester,
} from '@/actions/App/Http/Controllers/AdminSemesterController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type SemesterItem = {
    id: number;
    semester: string;
    start: string | null;
    end: string | null;
    attendancesCount: number;
    deletedAt: string | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { status: string };
type Props = {
    semesters: { data: SemesterItem[]; links: PaginationLink[] };
    filters: Filters;
};

const ALL = '__all__';

function formatDate(value: string | null): string {
    if (!value) {
        return 'Unbekannt';
    }

    return new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium' }).format(
        new Date(value),
    );
}

export default function AdminSemestersIndex({ semesters, filters }: Props) {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [editingSemester, setEditingSemester] = useState<SemesterItem | null>(
        null,
    );
    const filterForm = useForm<Filters>(filters);
    const createForm = useForm({
        semester: '',
        start: '',
        end: '',
    });
    const editForm = useForm({
        semester: '',
        start: '',
        end: '',
    });

    function submitCreate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        createForm.submit(storeSemester(), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setIsCreateDialogOpen(false);
            },
        });
    }

    function openEditDialog(semester: SemesterItem): void {
        setEditingSemester(semester);
        editForm.setData({
            semester: semester.semester,
            start: semester.start ?? '',
            end: semester.end ?? '',
        });
        editForm.clearErrors();
    }

    function submitUpdate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (!editingSemester) {
            return;
        }

        editForm.submit(updateSemester(editingSemester.id), {
            preserveScroll: true,
            onSuccess: () => setEditingSemester(null),
        });
    }

    return (
        <>
            <Head title="Semesterverwaltung" />
            <div className="flex flex-col gap-5 px-4 py-5 md:px-6 md:py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Semesterverwaltung
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Lege Semester an, passe Zeiträume an und archiviere
                            ältere Abschnitte.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Select
                            value={filterForm.data.status || ALL}
                            onValueChange={(value) =>
                                router.visit(
                                    adminSemestersIndex({
                                        query: {
                                            status: value === ALL ? '' : value,
                                        },
                                    }),
                                    {
                                        preserveScroll: true,
                                        preserveState: true,
                                        replace: true,
                                    },
                                )
                            }
                        >
                            <SelectTrigger className="w-full sm:w-44">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>Alle</SelectItem>
                                <SelectItem value="active">Aktiv</SelectItem>
                                <SelectItem value="archived">
                                    Archiviert
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Dialog
                            open={isCreateDialogOpen}
                            onOpenChange={setIsCreateDialogOpen}
                        >
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="size-4" />
                                    Neues Semester
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>Semester anlegen</DialogTitle>
                                    <DialogDescription>
                                        Das Semester wird direkt als
                                        auswählbarer Referenzwert verfügbar.
                                    </DialogDescription>
                                </DialogHeader>
                                <form
                                    onSubmit={submitCreate}
                                    className="space-y-5"
                                >
                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="create-semester">
                                                Bezeichnung
                                            </Label>
                                            <Input
                                                id="create-semester"
                                                value={createForm.data.semester}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'semester',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="WS 2025/2026"
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.semester
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="create-start">
                                                    Start
                                                </Label>
                                                <Input
                                                    id="create-start"
                                                    type="date"
                                                    value={
                                                        createForm.data.start
                                                    }
                                                    onChange={(event) =>
                                                        createForm.setData(
                                                            'start',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        createForm.errors.start
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="create-end">
                                                    Ende
                                                </Label>
                                                <Input
                                                    id="create-end"
                                                    type="date"
                                                    value={createForm.data.end}
                                                    onChange={(event) =>
                                                        createForm.setData(
                                                            'end',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        createForm.errors.end
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <Button
                                            type="submit"
                                            disabled={createForm.processing}
                                        >
                                            Semester speichern
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setIsCreateDialogOpen(false)
                                            }
                                        >
                                            Abbrechen
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <CalendarRange className="size-4 text-muted-foreground" />
                            <CardTitle>Semester</CardTitle>
                        </div>
                        <CardDescription>
                            Aktive und archivierte Semester inklusive
                            zugeordneter Einsätze.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {semesters.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed px-6 py-10 text-center">
                                <p className="text-sm font-medium">
                                    Keine Semester für diese Auswahl gefunden.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {semesters.data.map((semester) => (
                                    <article
                                        key={semester.id}
                                        className="rounded-xl border bg-muted/20 p-4"
                                    >
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div className="space-y-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <div className="flex items-center gap-2 text-sm font-medium">
                                                        <CalendarDays className="size-4 text-muted-foreground" />
                                                        <span>
                                                            {semester.semester}
                                                        </span>
                                                    </div>
                                                    <Badge
                                                        variant={
                                                            semester.deletedAt
                                                                ? 'outline'
                                                                : 'success'
                                                        }
                                                    >
                                                        {semester.deletedAt
                                                            ? 'Archiviert'
                                                            : 'Aktiv'}
                                                    </Badge>
                                                </div>
                                                <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-3">
                                                    <div>
                                                        Start:{' '}
                                                        {formatDate(
                                                            semester.start,
                                                        )}
                                                    </div>
                                                    <div>
                                                        Ende:{' '}
                                                        {formatDate(
                                                            semester.end,
                                                        )}
                                                    </div>
                                                    <div>
                                                        {
                                                            semester.attendancesCount
                                                        }{' '}
                                                        Einsätze
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {!semester.deletedAt && (
                                                    <>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                openEditDialog(
                                                                    semester,
                                                                )
                                                            }
                                                        >
                                                            <PencilLine className="size-4" />
                                                            Bearbeiten
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.visit(
                                                                    destroySemester(
                                                                        semester.id,
                                                                    ),
                                                                    {
                                                                        method: 'delete',
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <Archive className="size-4" />
                                                            Archivieren
                                                        </Button>
                                                    </>
                                                )}
                                                {semester.deletedAt && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            router.visit(
                                                                restoreSemester(
                                                                    semester.id,
                                                                ),
                                                                {
                                                                    method: 'patch',
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        <RotateCcw className="size-4" />
                                                        Wiederherstellen
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                        {semesters.links.length > 3 && (
                            <div className="flex flex-wrap gap-2 pt-2">
                                {semesters.links.map((link, index) => (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        type="button"
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        disabled={link.url === null}
                                        asChild={link.url !== null}
                                    >
                                        {link.url ? (
                                            <Link href={link.url}>
                                                {link.label
                                                    .replace(
                                                        '&laquo; Previous',
                                                        'Zurück',
                                                    )
                                                    .replace(
                                                        'Next &raquo;',
                                                        'Weiter',
                                                    )}
                                            </Link>
                                        ) : (
                                            <span>
                                                {link.label
                                                    .replace(
                                                        '&laquo; Previous',
                                                        'Zurück',
                                                    )
                                                    .replace(
                                                        'Next &raquo;',
                                                        'Weiter',
                                                    )}
                                            </span>
                                        )}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={editingSemester !== null}
                onOpenChange={(open) => !open && setEditingSemester(null)}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Semester bearbeiten</DialogTitle>
                        <DialogDescription>
                            Änderungen an der Bezeichnung werden in bestehenden
                            Einsätzen mitgeführt.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitUpdate} className="space-y-5">
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-semester">
                                    Bezeichnung
                                </Label>
                                <Input
                                    id="edit-semester"
                                    value={editForm.data.semester}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'semester',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={editForm.errors.semester}
                                />
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-start">Start</Label>
                                    <Input
                                        id="edit-start"
                                        type="date"
                                        value={editForm.data.start}
                                        onChange={(event) =>
                                            editForm.setData(
                                                'start',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={editForm.errors.start}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-end">Ende</Label>
                                    <Input
                                        id="edit-end"
                                        type="date"
                                        value={editForm.data.end}
                                        onChange={(event) =>
                                            editForm.setData(
                                                'end',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={editForm.errors.end} />
                                </div>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                            >
                                Änderungen speichern
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingSemester(null)}
                            >
                                Abbrechen
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminSemestersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Semester',
            href: adminSemestersIndex(),
        },
    ],
};
