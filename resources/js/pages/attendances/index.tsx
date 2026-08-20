import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    ChevronDown,
    ClipboardList,
    Clock3,
    Filter,
    GraduationCap,
    MonitorSmartphone,
    PencilLine,
    Plus,
    School,
    Trash2,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import AttendanceController, {
    index as attendancesIndex,
} from '@/actions/App/Http/Controllers/AttendanceController';
import InputError from '@/components/input-error';
import { LocalizedDateInput } from '@/components/localized-date-input';
import { PaginationLinks } from '@/components/pagination-links';
import { TimeInput } from '@/components/time-input';
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
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
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
    visitors: number;
    topics: string[];
    topicKeys: string[];
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
        semesterRanges: Record<string, { start: string; end: string }>;
        degreeFaculties: Record<string, string | null>;
    };
    canCreateEntries: boolean;
};

function currentBerlinDate(): string {
    const dateParts = new Intl.DateTimeFormat('de-DE', {
        timeZone: 'Europe/Berlin',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    })
        .formatToParts(new Date())
        .reduce<Record<string, string>>((parts, part) => {
            parts[part.type] = part.value;

            return parts;
        }, {});

    return `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
}

export default function AttendancesIndex({
    attendances,
    filters,
    formOptions,
    canCreateEntries,
}: Props) {
    const activeFilterCount = Object.values(filters).filter(
        (value) => value !== '',
    ).length;
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [editingAttendance, setEditingAttendance] =
        useState<AttendanceItem | null>(null);
    const [deletingAttendance, setDeletingAttendance] =
        useState<AttendanceItem | null>(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const today = currentBerlinDate();
    const defaultSemester =
        formOptions.semesters.find((semester) => {
            const range = formOptions.semesterRanges[semester];

            return range && range.start <= today && range.end >= today;
        }) ??
        formOptions.semesters.find(
            (semester) => formOptions.semesterRanges[semester]?.start <= today,
        ) ??
        formOptions.semesters[0] ??
        '';
    const defaultSemesterRange = formOptions.semesterRanges[defaultSemester];
    const defaultDate = defaultSemesterRange
        ? today < defaultSemesterRange.start
            ? defaultSemesterRange.start
            : today > defaultSemesterRange.end
              ? defaultSemesterRange.end
              : today
        : today;
    const defaultDegree = formOptions.degrees[0] ?? '';
    const createForm = useForm({
        semester: defaultSemester,
        date: defaultDate,
        startTime: '09:00',
        endTime: '10:00',
        degree: defaultDegree,
        faculty:
            formOptions.degreeFaculties[defaultDegree] ??
            formOptions.faculties[0] ??
            '',
        topics: [] as string[],
        online: false,
        visitors: 1,
    });
    const editForm = useForm({
        semester: '',
        date: '',
        startTime: '',
        endTime: '',
        degree: '',
        faculty: '',
        topics: [] as string[],
        online: false,
        visitors: 1,
    });
    const deleteForm = useForm({});
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

    function selectFilterDegree(degree: string): void {
        const selectedDegree = degree === FILTER_ALL ? '' : degree;

        filterForm.setData({
            ...filterForm.data,
            degree: selectedDegree,
            faculty:
                formOptions.degreeFaculties[selectedDegree] ??
                filterForm.data.faculty,
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

    function selectCreateDegree(degree: string): void {
        createForm.setData({
            ...createForm.data,
            degree,
            faculty:
                formOptions.degreeFaculties[degree] ?? createForm.data.faculty,
        });
    }

    function selectCreateSemester(semester: string): void {
        const range = formOptions.semesterRanges[semester];
        const date = range
            ? createForm.data.date < range.start
                ? range.start
                : createForm.data.date > range.end
                  ? range.end
                  : createForm.data.date
            : createForm.data.date;

        createForm.setData({ ...createForm.data, semester, date });
    }

    function openEditDialog(attendance: AttendanceItem): void {
        setEditingAttendance(attendance);
        editForm.setData({
            semester: attendance.semester,
            date: attendance.date,
            startTime: attendance.startTime,
            endTime: attendance.endTime,
            degree: attendance.degree,
            faculty: attendance.faculty,
            topics: attendance.topicKeys,
            online: attendance.online,
            visitors: attendance.visitors,
        });
        editForm.clearErrors();
    }

    function submitUpdate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (!editingAttendance) {
            return;
        }

        editForm.submit(AttendanceController.update(editingAttendance.id), {
            preserveScroll: true,
            onSuccess: () => setEditingAttendance(null),
        });
    }

    function deleteAttendance(): void {
        if (!deletingAttendance) {
            return;
        }

        deleteForm.submit(AttendanceController.destroy(deletingAttendance.id), {
            preserveScroll: true,
            onSuccess: () => setDeletingAttendance(null),
        });
    }

    function toggleEditTopic(topic: string, checked: boolean): void {
        editForm.setData(
            'topics',
            checked
                ? [...editForm.data.topics, topic]
                : editForm.data.topics.filter((value) => value !== topic),
        );
    }

    function selectEditDegree(degree: string): void {
        editForm.setData({
            ...editForm.data,
            degree,
            faculty:
                formOptions.degreeFaculties[degree] ?? editForm.data.faculty,
        });
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
                            bisherigen Einträge im Blick.
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
                                        Der Eintrag wird automatisch deinem
                                        Login-Namen zugeordnet.
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
                                                onValueChange={
                                                    selectCreateSemester
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
                                            <LocalizedDateInput
                                                id="date"
                                                required
                                                value={createForm.data.date}
                                                onChange={(value) =>
                                                    createForm.setData(
                                                        'date',
                                                        value,
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
                                            <TimeInput
                                                id="startTime"
                                                required
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
                                            <TimeInput
                                                id="endTime"
                                                required
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
                                                onValueChange={
                                                    selectCreateDegree
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
                                                <SelectTrigger
                                                    className="w-full"
                                                    disabled={Boolean(
                                                        formOptions
                                                            .degreeFaculties[
                                                            createForm.data
                                                                .degree
                                                        ],
                                                    )}
                                                >
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
                                            {formOptions.degreeFaculties[
                                                createForm.data.degree
                                            ] && (
                                                <p className="text-xs text-muted-foreground">
                                                    Wird automatisch aus dem
                                                    Studiengang übernommen.
                                                </p>
                                            )}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="visitors">
                                                Anzahl Besucher:innen
                                            </Label>
                                            <Input
                                                id="visitors"
                                                type="number"
                                                min="1"
                                                max="1000"
                                                required
                                                value={createForm.data.visitors}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'visitors',
                                                        Number(
                                                            event.target.value,
                                                        ),
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.visitors
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
                                                Aktiviere diese Option für einen
                                                Online-Termin.
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
                                                filterForm.data.semester ||
                                                FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'semester',
                                                    value === FILTER_ALL
                                                        ? ''
                                                        : value,
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
                                                filterForm.data.degree ||
                                                FILTER_ALL
                                            }
                                            onValueChange={selectFilterDegree}
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
                                                filterForm.data.faculty ||
                                                FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'faculty',
                                                    value === FILTER_ALL
                                                        ? ''
                                                        : value,
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
                                            value={
                                                filterForm.data.topic ||
                                                FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'topic',
                                                    value === FILTER_ALL
                                                        ? ''
                                                        : value,
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
                                                filterForm.data.online ||
                                                FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'online',
                                                    value === FILTER_ALL
                                                        ? ''
                                                        : value,
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
                                            <LocalizedDateInput
                                                id="from"
                                                value={filterForm.data.from}
                                                onChange={(value) =>
                                                    filterForm.setData(
                                                        'from',
                                                        value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="until">Bis</Label>
                                            <LocalizedDateInput
                                                id="until"
                                                value={filterForm.data.until}
                                                onChange={(value) =>
                                                    filterForm.setData(
                                                        'until',
                                                        value,
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
                                        data-testid="attendance-card"
                                        className="overflow-hidden rounded-xl border bg-card shadow-xs"
                                    >
                                        <div className="space-y-4 p-4">
                                            <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
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
                                                                ? 'info'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        <MonitorSmartphone className="size-3.5" />
                                                        {attendance.online
                                                            ? 'Online'
                                                            : 'Präsenz'}
                                                    </Badge>
                                                    <Badge variant="outline">
                                                        <Users className="size-3.5" />
                                                        {attendance.visitors}{' '}
                                                        {attendance.visitors ===
                                                        1
                                                            ? 'Besucher:in'
                                                            : 'Besucher:innen'}
                                                    </Badge>
                                                </div>
                                                <div className="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="w-full sm:w-auto"
                                                        onClick={() =>
                                                            openEditDialog(
                                                                attendance,
                                                            )
                                                        }
                                                    >
                                                        <PencilLine className="size-3.5" />
                                                        Bearbeiten
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="w-full border-destructive/30 text-destructive hover:bg-destructive/10 hover:text-destructive sm:w-auto dark:border-destructive/40"
                                                        onClick={() =>
                                                            setDeletingAttendance(
                                                                attendance,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                        Löschen
                                                    </Button>
                                                </div>
                                            </div>
                                            <dl
                                                data-testid="attendance-card-details"
                                                className="grid divide-y border-t pt-4 sm:grid-cols-3 sm:divide-x sm:divide-y-0"
                                            >
                                                <div className="flex items-center justify-between gap-3 py-2.5 sm:block sm:px-4 sm:py-3 sm:first:pl-0">
                                                    <dt className="flex items-center gap-2 text-xs text-muted-foreground">
                                                        <School className="size-4 shrink-0" />
                                                        Semester
                                                    </dt>
                                                    <dd className="text-right text-sm font-medium sm:mt-1.5 sm:text-left">
                                                        {attendance.semester}
                                                    </dd>
                                                </div>
                                                <div className="flex items-center justify-between gap-3 py-2.5 sm:block sm:px-4 sm:py-3">
                                                    <dt className="flex items-center gap-2 text-xs text-muted-foreground">
                                                        <GraduationCap className="size-4 shrink-0" />
                                                        Studiengang
                                                    </dt>
                                                    <dd className="text-right text-sm font-medium sm:mt-1.5 sm:text-left">
                                                        {attendance.degree}
                                                    </dd>
                                                </div>
                                                <div className="flex items-center justify-between gap-3 py-2.5 sm:block sm:px-4 sm:py-3">
                                                    <dt className="flex items-center gap-2 text-xs text-muted-foreground">
                                                        <Filter className="size-4 shrink-0" />
                                                        Fachbereich
                                                    </dt>
                                                    <dd className="text-right text-sm font-medium sm:mt-1.5 sm:text-left">
                                                        {attendance.faculty}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>
                                        {attendance.topics.length > 0 && (
                                            <div className="flex flex-col gap-2 border-t bg-muted/15 px-4 py-3 sm:flex-row sm:items-center">
                                                <span className="shrink-0 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                    Themen
                                                </span>
                                                <div className="flex flex-wrap gap-2">
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
                                        )}
                                    </article>
                                ))}
                            </div>
                        )}
                        <PaginationLinks links={attendances.links} />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={editingAttendance !== null}
                onOpenChange={(open) => !open && setEditingAttendance(null)}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Eintrag bearbeiten</DialogTitle>
                        <DialogDescription>
                            Korrigiere die Angaben dieser Beratung. Die
                            Zuordnung zu deinem Login bleibt unverändert.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitUpdate} className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Semester</Label>
                                <Select
                                    value={editForm.data.semester}
                                    onValueChange={(value) =>
                                        editForm.setData('semester', value)
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
                                    message={editForm.errors.semester}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-date">Datum</Label>
                                <LocalizedDateInput
                                    id="edit-date"
                                    required
                                    value={editForm.data.date}
                                    onChange={(value) =>
                                        editForm.setData('date', value)
                                    }
                                />
                                <InputError message={editForm.errors.date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-start-time">
                                    Startzeit
                                </Label>
                                <TimeInput
                                    id="edit-start-time"
                                    required
                                    value={editForm.data.startTime}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'startTime',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={editForm.errors.startTime}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-end-time">Endzeit</Label>
                                <TimeInput
                                    id="edit-end-time"
                                    required
                                    value={editForm.data.endTime}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'endTime',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={editForm.errors.endTime} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Studiengang</Label>
                                <Select
                                    value={editForm.data.degree}
                                    onValueChange={selectEditDegree}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Studiengang wählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formOptions.degrees.map((degree) => (
                                            <SelectItem
                                                key={degree}
                                                value={degree}
                                            >
                                                {degree}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editForm.errors.degree} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Fachbereich</Label>
                                <Select
                                    value={editForm.data.faculty}
                                    onValueChange={(value) =>
                                        editForm.setData('faculty', value)
                                    }
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        disabled={Boolean(
                                            formOptions.degreeFaculties[
                                                editForm.data.degree
                                            ],
                                        )}
                                    >
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
                                <InputError message={editForm.errors.faculty} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-visitors">
                                    Anzahl Besucher:innen
                                </Label>
                                <Input
                                    id="edit-visitors"
                                    type="number"
                                    min="1"
                                    max="1000"
                                    required
                                    value={editForm.data.visitors}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'visitors',
                                            Number(event.target.value),
                                        )
                                    }
                                />
                                <InputError
                                    message={editForm.errors.visitors}
                                />
                            </div>
                        </div>
                        <div className="grid gap-3">
                            <Label>Themen</Label>
                            <div className="grid gap-3 md:grid-cols-2">
                                {formOptions.topics.map((topic) => (
                                    <label
                                        key={topic.value}
                                        className="flex items-start gap-3 rounded-lg border p-3"
                                    >
                                        <Checkbox
                                            checked={editForm.data.topics.includes(
                                                topic.value,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleEditTopic(
                                                    topic.value,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <span className="text-sm font-medium">
                                            {topic.label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={editForm.errors.topics} />
                        </div>
                        <label className="flex items-center gap-3 rounded-lg border p-4">
                            <Checkbox
                                checked={editForm.data.online}
                                onCheckedChange={(checked) =>
                                    editForm.setData('online', checked === true)
                                }
                            />
                            <span className="text-sm font-medium">
                                Online-Beratung
                            </span>
                        </label>
                        <div className="flex flex-wrap gap-3">
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                            >
                                {editForm.processing && (
                                    <Spinner className="mr-2" />
                                )}
                                Änderungen speichern
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingAttendance(null)}
                            >
                                Abbrechen
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deletingAttendance !== null}
                onOpenChange={(open) => !open && setDeletingAttendance(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eintrag löschen?</DialogTitle>
                        <DialogDescription>
                            Die Beratung vom {deletingAttendance?.date} um{' '}
                            {deletingAttendance?.startTime} Uhr wird aus den
                            Listen und Statistiken entfernt.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingAttendance(null)}
                        >
                            Abbrechen
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={deleteForm.processing}
                            onClick={deleteAttendance}
                        >
                            {deleteForm.processing && (
                                <Spinner className="mr-2" />
                            )}
                            Eintrag löschen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
