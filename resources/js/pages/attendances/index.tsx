import { Head, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    CalendarDays,
    ChevronDown,
    ClipboardList,
    Clock3,
    Filter,
    GraduationCap,
    MonitorSmartphone,
    Plus,
    School,
} from 'lucide-react';
import AttendanceController, {
    index as attendancesIndex,
} from '@/actions/App/Http/Controllers/AttendanceController';
import InputError from '@/components/input-error';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
} from '@/components/ui/collapsible';
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
import { Spinner } from '@/components/ui/spinner';
import { FILTER_ALL } from '@/lib/constants';
import { cleanFilters } from '@/lib/filters';

type AttendanceItem = {
    id: number;
    date: string;
    startTime: string;
    endTime: string;
    semester: string;
    degree: string;
    faculty: string;
    online: boolean;
    topics: string[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type TopicOption = { value: string; label: string };
type Filters = {
    semester: string;
    degree: string;
    faculty: string;
    topic: string;
    online: string;
    from: string;
    until: string;
};

type Props = {
    attendances: { data: AttendanceItem[]; links: PaginationLink[] };
    filters: Filters;
    formOptions: {
        semesters: string[];
        degrees: string[];
        faculties: string[];
        topics: TopicOption[];
    };
    canCreateEntries: boolean;
};

export default function AttendancesIndex({
    attendances,
    filters,
    formOptions,
    canCreateEntries,
}: Props) {
    const hasActiveFilters = Object.values(filters).some(
        (value) => value !== '',
    );
    const activeFilterCount = Object.values(filters).filter(
        (value) => value !== '',
    ).length;
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const createForm = useForm({
        semester: formOptions.semesters[0] ?? '',
        date: new Date().toISOString().slice(0, 10),
        startTime: '09:00',
        endTime: '10:00',
        degree: formOptions.degrees[0] ?? '',
        faculty: formOptions.faculties[0] ?? '',
        topics: [] as string[],
        online: false,
    });
    const filterForm = useForm<Filters>(filters);

    function submitEntry(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        createForm.submit(AttendanceController.store(), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset('date', 'startTime', 'endTime', 'topics');
                setIsCreateDialogOpen(false);
            },
            onError: () => {
                // errors are shown inline — keep dialog open
            },
        });
    }

    function applyFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        router.visit(
            attendancesIndex({ query: cleanFilters(filterForm.data) }),
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    function resetFilters(): void {
        filterForm.setData({
            semester: '',
            degree: '',
            faculty: '',
            topic: '',
            online: '',
            from: '',
            until: '',
        });
        router.visit(attendancesIndex(), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function toggleTopic(topic: string, checked: boolean): void {
        createForm.setData(
            'topics',
            checked
                ? [...createForm.data.topics, topic]
                : createForm.data.topics.filter((value) => value !== topic),
        );
    }

    return (
        <>
            <Head title="Anwesenheiten" />
            <div className="flex flex-col gap-5 px-4 py-5 md:px-6 md:py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Anwesenheiten
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Erfasse Helpdesk-Einsätze und behalte deine
                            bisherigen Tutoreneinträge im Blick.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            className="justify-between"
                            onClick={() => setIsFilterOpen((open) => !open)}
                        >
                            <span className="flex items-center gap-2">
                                <Filter className="size-4" />
                                Filter
                                {activeFilterCount > 0 && (
                                    <Badge variant="secondary">
                                        {activeFilterCount}
                                    </Badge>
                                )}
                            </span>
                            <ChevronDown
                                className={`size-4 transition-transform ${
                                    isFilterOpen ? 'rotate-180' : ''
                                }`}
                            />
                        </Button>
                        <Dialog
                            open={isCreateDialogOpen}
                            onOpenChange={setIsCreateDialogOpen}
                        >
                            <DialogTrigger asChild>
                                <Button className="w-full lg:w-auto">
                                    <Plus className="size-4" />
                                    Neuer Eintrag
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                                <DialogHeader>
                                    <DialogTitle>
                                        Neuen Eintrag erstellen
                                    </DialogTitle>
                                    <DialogDescription>
                                        Der Eintrag wird automatisch dir als
                                        eingeloggtem Tutor zugeordnet.
                                    </DialogDescription>
                                </DialogHeader>
                                {!canCreateEntries && (
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                        Es fehlen Stammdaten für Semester,
                                        Studiengänge oder Fachbereiche.
                                    </div>
                                )}
                                <form
                                    onSubmit={submitEntry}
                                    className="space-y-6"
                                >
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label>Semester</Label>
                                            <Select
                                                value={createForm.data.semester}
                                                onValueChange={(value) =>
                                                    createForm.setData(
                                                        'semester',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Semester wählen" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {formOptions.semesters.map(
                                                        (semester) => (
                                                            <SelectItem
                                                                key={semester}
                                                                value={semester}
                                                            >
                                                                {semester}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    createForm.errors.semester
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="date">Datum</Label>
                                            <Input
                                                id="date"
                                                type="date"
                                                value={createForm.data.date}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'date',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={createForm.errors.date}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="startTime">
                                                Startzeit
                                            </Label>
                                            <Input
                                                id="startTime"
                                                type="time"
                                                value={
                                                    createForm.data.startTime
                                                }
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'startTime',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.startTime
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="endTime">
                                                Endzeit
                                            </Label>
                                            <Input
                                                id="endTime"
                                                type="time"
                                                value={createForm.data.endTime}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'endTime',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.endTime
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Studiengang</Label>
                                            <Select
                                                value={createForm.data.degree}
                                                onValueChange={(value) =>
                                                    createForm.setData(
                                                        'degree',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Studiengang wählen" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {formOptions.degrees.map(
                                                        (degree) => (
                                                            <SelectItem
                                                                key={degree}
                                                                value={degree}
                                                            >
                                                                {degree}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    createForm.errors.degree
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Fachbereich</Label>
                                            <Select
                                                value={createForm.data.faculty}
                                                onValueChange={(value) =>
                                                    createForm.setData(
                                                        'faculty',
                                                        value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Fachbereich wählen" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {formOptions.faculties.map(
                                                        (faculty) => (
                                                            <SelectItem
                                                                key={faculty}
                                                                value={faculty}
                                                            >
                                                                {faculty}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    createForm.errors.faculty
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-3">
                                        <Label>Themen</Label>
                                        <div className="grid gap-3 md:grid-cols-2">
                                            {formOptions.topics.map((topic) => (
                                                <label
                                                    key={topic.value}
                                                    className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/40"
                                                >
                                                    <Checkbox
                                                        checked={createForm.data.topics.includes(
                                                            topic.value,
                                                        )}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggleTopic(
                                                                topic.value,
                                                                checked ===
                                                                    true,
                                                            )
                                                        }
                                                    />
                                                    <span className="text-sm font-medium">
                                                        {topic.label}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError
                                            message={createForm.errors.topics}
                                        />
                                    </div>
                                    <div className="flex items-center gap-3 rounded-lg border p-4">
                                        <Checkbox
                                            checked={createForm.data.online}
                                            onCheckedChange={(checked) =>
                                                createForm.setData(
                                                    'online',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium">
                                                Online-Einsatz
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Aktiviere diese Option für
                                                einen Online-Termin.
                                            </p>
                                        </div>
                                    </div>
                                    <InputError
                                        message={createForm.errors.online}
                                    />
                                    <div className="flex flex-wrap gap-3">
                                        <Button
                                            type="submit"
                                            disabled={
                                                createForm.processing ||
                                                !canCreateEntries
                                            }
                                        >
                                            {createForm.processing && (
                                                <Spinner className="mr-2" />
                                            )}
                                            Eintrag speichern
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

                <Collapsible open={isFilterOpen} onOpenChange={setIsFilterOpen}>
                    <CollapsibleContent>
                        <div className="rounded-xl border bg-muted/20 p-4 md:p-5">
                            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Begrenze deine Liste nach Semester, Thema,
                                    Modus oder Zeitraum.
                                </p>
                                {activeFilterCount > 0 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={resetFilters}
                                    >
                                        Zurücksetzen
                                    </Button>
                                )}
                            </div>
                            <form onSubmit={applyFilters} className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label>Semester</Label>
                                        <Select
                                            value={
                                                filterForm.data.semester || FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'semester',
                                                    value === FILTER_ALL ? '' : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Alle Semester" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={FILTER_ALL}>
                                                    Alle Semester
                                                </SelectItem>
                                                {formOptions.semesters.map(
                                                    (semester) => (
                                                        <SelectItem
                                                            key={semester}
                                                            value={semester}
                                                        >
                                                            {semester}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Studiengang</Label>
                                        <Select
                                            value={
                                                filterForm.data.degree || FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'degree',
                                                    value === FILTER_ALL ? '' : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Alle Studiengänge" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={FILTER_ALL}>
                                                    Alle Studiengänge
                                                </SelectItem>
                                                {formOptions.degrees.map(
                                                    (degree) => (
                                                        <SelectItem
                                                            key={degree}
                                                            value={degree}
                                                        >
                                                            {degree}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Fachbereich</Label>
                                        <Select
                                            value={
                                                filterForm.data.faculty || FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'faculty',
                                                    value === FILTER_ALL ? '' : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Alle Fachbereiche" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={FILTER_ALL}>
                                                    Alle Fachbereiche
                                                </SelectItem>
                                                {formOptions.faculties.map(
                                                    (faculty) => (
                                                        <SelectItem
                                                            key={faculty}
                                                            value={faculty}
                                                        >
                                                            {faculty}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Thema</Label>
                                        <Select
                                            value={filterForm.data.topic || FILTER_ALL}
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'topic',
                                                    value === FILTER_ALL ? '' : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Alle Themen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={FILTER_ALL}>
                                                    Alle Themen
                                                </SelectItem>
                                                {formOptions.topics.map(
                                                    (topic) => (
                                                        <SelectItem
                                                            key={topic.value}
                                                            value={topic.value}
                                                        >
                                                            {topic.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Modus</Label>
                                        <Select
                                            value={
                                                filterForm.data.online || FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'online',
                                                    value === FILTER_ALL ? '' : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Alle Modi" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={FILTER_ALL}>
                                                    Alle Modi
                                                </SelectItem>
                                                <SelectItem value="0">
                                                    Präsenz
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    Online
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-2 xl:col-span-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="from">Von</Label>
                                            <Input
                                                id="from"
                                                type="date"
                                                value={filterForm.data.from}
                                                onChange={(event) =>
                                                    filterForm.setData(
                                                        'from',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="until">Bis</Label>
                                            <Input
                                                id="until"
                                                type="date"
                                                value={filterForm.data.until}
                                                onChange={(event) =>
                                                    filterForm.setData(
                                                        'until',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-3">
                                    <Button type="submit">
                                        Filter anwenden
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <ClipboardList className="size-4 text-muted-foreground" />
                            <CardTitle>Meine Einsätze</CardTitle>
                        </div>
                        <CardDescription>
                            Die Liste zeigt deine zuletzt erfassten
                            Helpdesk-Einsätze.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {attendances.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed px-6 py-10 text-center">
                                <p className="text-sm font-medium">
                                    Noch keine Einträge vorhanden.
                                </p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Lege über den Button oben deinen ersten
                                    Helpdesk-Einsatz an.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {attendances.data.map((attendance) => (
                                    <article
                                        key={attendance.id}
                                        className="rounded-xl border bg-muted/20 p-4"
                                    >
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div className="space-y-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Badge variant="secondary">
                                                        <CalendarDays className="size-3.5" />
                                                        {attendance.date}
                                                    </Badge>
                                                    <Badge variant="outline">
                                                        <Clock3 className="size-3.5" />
                                                        {attendance.startTime}-
                                                        {attendance.endTime}
                                                    </Badge>
                                                    <Badge
                                                        variant={
                                                            attendance.online
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                    >
                                                        <MonitorSmartphone className="size-3.5" />
                                                        {attendance.online
                                                            ? 'Online'
                                                            : 'Präsenz'}
                                                    </Badge>
                                                </div>
                                                <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-3">
                                                    <div className="flex items-center gap-2">
                                                        <School className="size-4" />
                                                        <span>
                                                            {
                                                                attendance.semester
                                                            }
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <GraduationCap className="size-4" />
                                                        <span>
                                                            {attendance.degree}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Filter className="size-4" />
                                                        <span>
                                                            {attendance.faculty}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-2 lg:max-w-md lg:justify-end">
                                                {attendance.topics.map(
                                                    (topic) => (
                                                        <Badge
                                                            key={topic}
                                                            variant="outline"
                                                            className="bg-background"
                                                        >
                                                            {topic}
                                                        </Badge>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                        <PaginationLinks links={attendances.links} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AttendancesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Anwesenheiten',
            href: attendancesIndex(),
        },
    ],
};
