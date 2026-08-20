import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    ChevronDown,
    Clock3,
    Filter,
    GraduationCap,
    Mail,
    MonitorSmartphone,
    PencilLine,
    School,
    Shield,
    Trash2,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import AttendanceController, {
    adminIndex as adminAttendancesIndex,
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
    tutor: {
        id: number | null;
        name: string | null;
        email: string | null;
    } | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type SelectOption = { value: string; label: string };
type TutorOption = SelectOption & { email: string };
type Filters = {
    user: string;
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
        topics: SelectOption[];
        tutors: TutorOption[];
        semesterRanges: Record<string, { start: string; end: string }>;
        degreeFaculties: Record<string, string | null>;
    };
};

export default function AdminAttendancesIndex({
    attendances,
    filters,
    formOptions,
}: Props) {
    const activeFilterCount = Object.values(filters).filter(
        (value) => value !== '',
    ).length;
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [editingAttendance, setEditingAttendance] =
        useState<AttendanceItem | null>(null);
    const [deletingAttendance, setDeletingAttendance] =
        useState<AttendanceItem | null>(null);
    const filterForm = useForm<Filters>(filters);
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

    function applyFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        router.visit(
            adminAttendancesIndex({ query: cleanFilters(filterForm.data) }),
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    function resetFilters(): void {
        filterForm.setData({
            user: '',
            semester: '',
            degree: '',
            faculty: '',
            topic: '',
            online: '',
            from: '',
            until: '',
        });

        router.visit(adminAttendancesIndex(), {
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

    function selectEditDegree(degree: string): void {
        editForm.setData({
            ...editForm.data,
            degree,
            faculty:
                formOptions.degreeFaculties[degree] ?? editForm.data.faculty,
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

    return (
        <>
            <Head title="Admin Anwesenheiten" />
            <div className="flex flex-col gap-5 px-4 py-5 md:px-6 md:py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Admin-Ansicht
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Behalte alle Helpdesk-Einsätze der Tutorinnen und
                            Tutoren im Blick.
                        </p>
                    </div>
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
                </div>

                <Collapsible open={isFilterOpen} onOpenChange={setIsFilterOpen}>
                    <CollapsibleContent>
                        <div className="rounded-xl border bg-muted/20 p-4 md:p-5">
                            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Filtere nach Tutor, Semester, Thema, Modus
                                    oder Zeitraum.
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
                                        <Label>Tutor</Label>
                                        <Select
                                            value={
                                                filterForm.data.user ||
                                                FILTER_ALL
                                            }
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'user',
                                                    value === FILTER_ALL
                                                        ? ''
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Alle Tutor:innen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={FILTER_ALL}>
                                                    Alle Tutor:innen
                                                </SelectItem>
                                                {formOptions.tutors.map(
                                                    (tutor) => (
                                                        <SelectItem
                                                            key={tutor.value}
                                                            value={tutor.value}
                                                        >
                                                            {tutor.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
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
                            <Shield className="size-4 text-muted-foreground" />
                            <CardTitle>Alle Einsätze</CardTitle>
                        </div>
                        <CardDescription>
                            Die Liste zeigt alle erfassten Helpdesk-Einsätze im
                            System.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {attendances.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed px-6 py-10 text-center">
                                <p className="text-sm font-medium">
                                    Keine Einsätze für diese Filter gefunden.
                                </p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Passe die Filter an, um weitere Einträge zu
                                    sehen.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {attendances.data.map((attendance) => (
                                    <article
                                        key={attendance.id}
                                        data-testid="admin-attendance-card"
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
                                                                ? 'default'
                                                                : 'outline'
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
                                                <div
                                                    data-testid="admin-attendance-card-actions"
                                                    className="grid grid-cols-2 gap-2 sm:flex sm:justify-end"
                                                >
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

                                            <div className="grid gap-4 border-t pt-4 lg:grid-cols-[minmax(16rem,1fr)_minmax(0,2fr)] lg:gap-0">
                                                <div className="flex min-w-0 items-center gap-3 lg:pr-6">
                                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <Users className="size-5" />
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-xs font-medium text-muted-foreground">
                                                            Tutor:in
                                                        </p>
                                                        <p className="truncate text-sm font-semibold">
                                                            {attendance.tutor
                                                                ?.name ??
                                                                'Unbekannt'}
                                                        </p>
                                                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                                            <Mail className="size-3.5 shrink-0" />
                                                            <span className="truncate">
                                                                {attendance
                                                                    .tutor
                                                                    ?.email ??
                                                                    'Keine E-Mail'}
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <dl
                                                    data-testid="admin-attendance-card-details"
                                                    className="divide-y border-y sm:grid sm:grid-cols-3 sm:divide-x sm:divide-y-0 lg:border-y-0 lg:border-l"
                                                >
                                                    <div className="flex items-center justify-between gap-3 py-2.5 sm:block sm:px-4 sm:py-0">
                                                        <dt className="flex items-center gap-2 text-xs text-muted-foreground">
                                                            <School className="size-4 shrink-0" />
                                                            Semester
                                                        </dt>
                                                        <dd className="text-right text-sm font-medium sm:mt-1.5 sm:text-left">
                                                            {
                                                                attendance.semester
                                                            }
                                                        </dd>
                                                    </div>
                                                    <div className="flex items-center justify-between gap-3 py-2.5 sm:block sm:px-4 sm:py-0">
                                                        <dt className="flex items-center gap-2 text-xs text-muted-foreground">
                                                            <GraduationCap className="size-4 shrink-0" />
                                                            Studiengang
                                                        </dt>
                                                        <dd className="text-right text-sm font-medium sm:mt-1.5 sm:text-left">
                                                            {attendance.degree}
                                                        </dd>
                                                    </div>
                                                    <div className="flex items-center justify-between gap-3 py-2.5 sm:block sm:px-4 sm:py-0">
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
                                        </div>
                                        {attendance.topics.length > 0 && (
                                            <div
                                                data-testid="admin-attendance-card-topics"
                                                className="flex flex-col gap-2 border-t bg-muted/15 px-4 py-3 sm:flex-row sm:items-center"
                                            >
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
                            Der Eintrag bleibt{' '}
                            {editingAttendance?.tutor?.name ??
                                'der bisherigen Person'}{' '}
                            zugeordnet.
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
                                <Label htmlFor="admin-edit-date">Datum</Label>
                                <LocalizedDateInput
                                    id="admin-edit-date"
                                    required
                                    value={editForm.data.date}
                                    onChange={(value) =>
                                        editForm.setData('date', value)
                                    }
                                />
                                <InputError message={editForm.errors.date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="admin-edit-start">
                                    Startzeit
                                </Label>
                                <TimeInput
                                    id="admin-edit-start"
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
                                <Label htmlFor="admin-edit-end">Endzeit</Label>
                                <TimeInput
                                    id="admin-edit-end"
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
                                <Label htmlFor="admin-edit-visitors">
                                    Anzahl Besucher:innen
                                </Label>
                                <Input
                                    id="admin-edit-visitors"
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

AdminAttendancesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: adminAttendancesIndex(),
        },
    ],
};
