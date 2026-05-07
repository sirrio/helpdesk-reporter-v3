import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Archive, Building2, PencilLine, Plus, RotateCcw } from 'lucide-react';
import {
    destroy as destroyFaculty,
    index as adminFacultiesIndex,
    restore as restoreFaculty,
    store as storeFaculty,
    update as updateFaculty,
} from '@/actions/App/Http/Controllers/AdminFacultyController';
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

type FacultyItem = {
    id: number;
    name: string;
    attendancesCount: number;
    deletedAt: string | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { status: string };
type Props = {
    faculties: { data: FacultyItem[]; links: PaginationLink[] };
    filters: Filters;
};

const ALL = '__all__';

export default function AdminFacultiesIndex({ faculties, filters }: Props) {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [editingFaculty, setEditingFaculty] = useState<FacultyItem | null>(null);
    const filterForm = useForm<Filters>(filters);
    const createForm = useForm({
        name: '',
    });
    const editForm = useForm({
        name: '',
    });

    function submitCreate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        createForm.submit(storeFaculty(), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setIsCreateDialogOpen(false);
            },
        });
    }

    function openEditDialog(faculty: FacultyItem): void {
        setEditingFaculty(faculty);
        editForm.setData({
            name: faculty.name,
        });
        editForm.clearErrors();
    }

    function submitUpdate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (!editingFaculty) {
            return;
        }

        editForm.submit(updateFaculty(editingFaculty.id), {
            preserveScroll: true,
            onSuccess: () => setEditingFaculty(null),
        });
    }

    return (
        <>
            <Head title="Fachbereiche" />
            <div className="flex flex-col gap-5 px-4 py-5 md:px-6 md:py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Fachbereiche
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Pflege die auswählbaren Fachbereiche für neue Helpdesk-Einsätze.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Select
                            value={filterForm.data.status || ALL}
                            onValueChange={(value) =>
                                router.visit(
                                    adminFacultiesIndex({
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
                                <SelectItem value="archived">Archiviert</SelectItem>
                            </SelectContent>
                        </Select>
                        <Dialog
                            open={isCreateDialogOpen}
                            onOpenChange={setIsCreateDialogOpen}
                        >
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="size-4" />
                                    Neuer Fachbereich
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>Fachbereich anlegen</DialogTitle>
                                    <DialogDescription>
                                        Der Wert steht danach sofort im Eingabeformular zur Auswahl.
                                    </DialogDescription>
                                </DialogHeader>
                                <form onSubmit={submitCreate} className="space-y-5">
                                    <div className="grid gap-2">
                                        <Label htmlFor="create-faculty">Bezeichnung</Label>
                                        <Input
                                            id="create-faculty"
                                            value={createForm.data.name}
                                            onChange={(event) =>
                                                createForm.setData('name', event.target.value)
                                            }
                                            placeholder="Kulturwissenschaften"
                                        />
                                        <InputError message={createForm.errors.name} />
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <Button type="submit" disabled={createForm.processing}>
                                            Fachbereich speichern
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setIsCreateDialogOpen(false)}
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
                            <Building2 className="size-4 text-muted-foreground" />
                            <CardTitle>Fachbereiche</CardTitle>
                        </div>
                        <CardDescription>
                            Aktive und archivierte Werte inklusive zugeordneter Einsätze.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {faculties.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed px-6 py-10 text-center">
                                <p className="text-sm font-medium">
                                    Keine Fachbereiche für diese Auswahl gefunden.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {faculties.data.map((faculty) => (
                                    <article
                                        key={faculty.id}
                                        className="rounded-xl border bg-muted/20 p-4"
                                    >
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div className="space-y-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <div className="flex items-center gap-2 text-sm font-medium">
                                                        <Building2 className="size-4 text-muted-foreground" />
                                                        <span>{faculty.name}</span>
                                                    </div>
                                                    <Badge variant={faculty.deletedAt ? 'outline' : 'secondary'}>
                                                        {faculty.deletedAt ? 'Archiviert' : 'Aktiv'}
                                                    </Badge>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {faculty.attendancesCount} Einsätze mit diesem Fachbereich
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {!faculty.deletedAt && (
                                                    <>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() => openEditDialog(faculty)}
                                                        >
                                                            <PencilLine className="size-4" />
                                                            Bearbeiten
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.visit(destroyFaculty(faculty.id), {
                                                                    method: 'delete',
                                                                    preserveScroll: true,
                                                                })
                                                            }
                                                        >
                                                            <Archive className="size-4" />
                                                            Archivieren
                                                        </Button>
                                                    </>
                                                )}
                                                {faculty.deletedAt && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            router.visit(restoreFaculty(faculty.id), {
                                                                method: 'patch',
                                                                preserveScroll: true,
                                                            })
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
                        {faculties.links.length > 3 && (
                            <div className="flex flex-wrap gap-2 pt-2">
                                {faculties.links.map((link, index) => (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        type="button"
                                        variant={link.active ? 'default' : 'outline'}
                                        disabled={link.url === null}
                                        asChild={link.url !== null}
                                    >
                                        {link.url ? (
                                            <Link href={link.url}>
                                                {link.label
                                                    .replace('&laquo; Previous', 'Zurück')
                                                    .replace('Next &raquo;', 'Weiter')}
                                            </Link>
                                        ) : (
                                            <span>
                                                {link.label
                                                    .replace('&laquo; Previous', 'Zurück')
                                                    .replace('Next &raquo;', 'Weiter')}
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
                open={editingFaculty !== null}
                onOpenChange={(open) => !open && setEditingFaculty(null)}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Fachbereich bearbeiten</DialogTitle>
                        <DialogDescription>
                            Änderungen an der Bezeichnung werden in bestehenden Einsätzen mitgeführt.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitUpdate} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-faculty">Bezeichnung</Label>
                            <Input
                                id="edit-faculty"
                                value={editForm.data.name}
                                onChange={(event) => editForm.setData('name', event.target.value)}
                            />
                            <InputError message={editForm.errors.name} />
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Button type="submit" disabled={editForm.processing}>
                                Änderungen speichern
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingFaculty(null)}
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

AdminFacultiesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Fachbereiche',
            href: adminFacultiesIndex(),
        },
    ],
};
