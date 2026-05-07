import { Head, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    CalendarDays,
    ChevronDown,
    ClipboardList,
    Clock3,
    Filter,
    GraduationCap,
    Mail,
    MonitorSmartphone,
    School,
    Shield,
    Users,
} from 'lucide-react';
import { adminIndex as adminAttendancesIndex } from '@/actions/App/Http/Controllers/AttendanceController';
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
import {
    Collapsible,
    CollapsibleContent,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    };
};

export default function AdminAttendancesIndex({
    attendances,
    filters,
    formOptions,
}: Props) {
    const hasActiveFilters = Object.values(filters).some(
        (value) => value !== '',
    );
    const activeFilterCount = Object.values(filters).filter(
        (value) => value !== '',
    ).length;
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const filterForm = useForm<Filters>(filters);

    function applyFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        router.visit(adminAttendancesIndex({ query: cleanFilters(filterForm.data) }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
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
                                            value={filterForm.data.user || FILTER_ALL}
                                            onValueChange={(value) =>
                                                filterForm.setData(
                                                    'user',
                                                    value === FILTER_ALL ? '' : value,
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
                                                {formOptions.tutors.map((tutor) => (
                                                    <SelectItem
                                                        key={tutor.value}
                                                        value={tutor.value}
                                                    >
                                                        {tutor.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Semester</Label>
                                        <Select
                                            value={filterForm.data.semester || FILTER_ALL}
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
                                                {formOptions.semesters.map((semester) => (
                                                    <SelectItem
                                                        key={semester}
                                                        value={semester}
                                                    >
                                                        {semester}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Studiengang</Label>
                                        <Select
                                            value={filterForm.data.degree || FILTER_ALL}
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
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Fachbereich</Label>
                                        <Select
                                            value={filterForm.data.faculty || FILTER_ALL}
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
                                                {formOptions.faculties.map((faculty) => (
                                                    <SelectItem
                                                        key={faculty}
                                                        value={faculty}
                                                    >
                                                        {faculty}
                                                    </SelectItem>
                                                ))}
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
                                                {formOptions.topics.map((topic) => (
                                                    <SelectItem
                                                        key={topic.value}
                                                        value={topic.value}
                                                    >
                                                        {topic.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Modus</Label>
                                        <Select
                                            value={filterForm.data.online || FILTER_ALL}
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
                                                <div className="grid gap-2 text-sm md:grid-cols-2">
                                                    <div className="flex items-center gap-2 text-foreground">
                                                        <Users className="size-4 text-muted-foreground" />
                                                        <span className="font-medium">
                                                            {attendance.tutor?.name ??
                                                                'Unbekannt'}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2 text-muted-foreground">
                                                        <Mail className="size-4" />
                                                        <span>
                                                            {attendance.tutor?.email ??
                                                                'Keine E-Mail'}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-3">
                                                    <div className="flex items-center gap-2">
                                                        <School className="size-4" />
                                                        <span>
                                                            {attendance.semester}
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
                                                {attendance.topics.map((topic) => (
                                                    <Badge
                                                        key={topic}
                                                        variant="outline"
                                                        className="bg-background"
                                                    >
                                                        {topic}
                                                    </Badge>
                                                ))}
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

AdminAttendancesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: adminAttendancesIndex(),
        },
    ],
};
