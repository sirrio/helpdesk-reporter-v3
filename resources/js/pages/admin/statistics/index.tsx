import { Head, router, useForm } from '@inertiajs/react';
import {
    BarChart3,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Download,
    Filter,
    GraduationCap,
    Layers3,
    MonitorSmartphone,
    Printer,
    School,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    statistics as adminStatisticsIndex,
    statisticsCsv as adminStatisticsCsv,
} from '@/actions/App/Http/Controllers/AttendanceController';
import { LocalizedDateInput } from '@/components/localized-date-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
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

type BreakdownItem = {
    label: string;
    entries: number;
};

type WeeklyDay = {
    label: string;
    date: string;
    entries: number;
};

type SemesterWeek = {
    start: string;
    label: string;
    rangeLabel: string;
    entries: number;
};

type Props = {
    filters: Filters;
    week: string;
    formOptions: {
        semesters: string[];
        degrees: string[];
        faculties: string[];
        topics: SelectOption[];
        tutors: TutorOption[];
        degreeFaculties: Record<string, string | null>;
    };
    stats: {
        totals: {
            entries: number;
            visitors: number;
            minutes: number;
            hours: number;
            activeTutors: number;
            semesters: number;
            onlineEntries: number;
            presenceEntries: number;
            onlinePercentage: number;
        };
        weekly: {
            current: string;
            previous: string;
            next: string;
            label: string;
            rangeLabel: string;
            totalEntries: number;
            days: WeeklyDay[];
            semesterWeeks: SemesterWeek[];
        };
        faculties: BreakdownItem[];
        degrees: BreakdownItem[];
        topics: BreakdownItem[];
    };
};

function percentage(value: number, max: number): number {
    if (value <= 0 || max <= 0) {
        return 0;
    }

    return Math.max(4, Math.round((value / max) * 100));
}

function activeFilterLabels(
    filters: Filters,
    formOptions: Props['formOptions'],
): string[] {
    return [
        filters.user &&
            `Tutor: ${formOptions.tutors.find((tutor) => tutor.value === filters.user)?.label ?? filters.user}`,
        filters.semester && `Semester: ${filters.semester}`,
        filters.degree && `Studiengang: ${filters.degree}`,
        filters.faculty && `Fachbereich: ${filters.faculty}`,
        filters.topic &&
            `Thema: ${formOptions.topics.find((topic) => topic.value === filters.topic)?.label ?? filters.topic}`,
        filters.online === '1' && 'Modus: Online',
        filters.online === '0' && 'Modus: Präsenz',
        filters.from && `Von: ${filters.from}`,
        filters.until && `Bis: ${filters.until}`,
    ].filter((label): label is string => Boolean(label));
}

function PrintBreakdown({
    title,
    items,
}: {
    title: string;
    items: BreakdownItem[];
}) {
    const maxEntries = Math.max(...items.map((item) => item.entries), 0);

    return (
        <section className="statistics-print-section">
            <h2>{title}</h2>
            {items.length === 0 ? (
                <p className="statistics-print-empty">Keine Daten vorhanden</p>
            ) : (
                <div className="statistics-print-breakdown">
                    {items.map((item) => (
                        <div key={item.label} className="statistics-print-row">
                            <div className="statistics-print-row-label">
                                <span>{item.label}</span>
                                <strong>{item.entries}</strong>
                            </div>
                            <div className="statistics-print-bar">
                                <span
                                    style={{
                                        width: `${percentage(item.entries, maxEntries)}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}

function PrintStatistics({ filters, formOptions, stats }: Props) {
    const filterLabels = activeFilterLabels(filters, formOptions);
    const maxWeeklyEntries = Math.max(
        ...stats.weekly.days.map((day) => day.entries),
        10,
    );

    return (
        <article
            className="statistics-print hidden print:block"
            data-testid="statistics-print-layout"
        >
            <header className="statistics-print-header">
                <div>
                    <h1>Helpdesk Reporter - Statistik</h1>
                    <p>
                        Auswertung {stats.weekly.label} ·{' '}
                        {stats.weekly.rangeLabel}
                    </p>
                </div>
                <p className="statistics-print-filter">
                    {filterLabels.length > 0
                        ? filterLabels.join(' · ')
                        : 'Alle Einträge, keine Filter aktiv'}
                </p>
            </header>

            <section
                className="statistics-print-totals"
                data-testid="statistics-print-totals"
            >
                <div>
                    <span>Beratungen</span>
                    <strong>{stats.totals.entries}</strong>
                    <small>{stats.totals.visitors} Besucher:innen</small>
                </div>
                <div>
                    <span>Gesamtzeit</span>
                    <strong>{stats.totals.hours} h</strong>
                    <small>{stats.totals.minutes} Minuten</small>
                </div>
                <div>
                    <span>Aktive Tutor:innen</span>
                    <strong>{stats.totals.activeTutors}</strong>
                    <small>{stats.totals.semesters} Semester</small>
                </div>
                <div>
                    <span>Online-Anteil</span>
                    <strong>{stats.totals.onlinePercentage}%</strong>
                    <small>
                        {stats.totals.onlineEntries} online /{' '}
                        {stats.totals.presenceEntries} präsent
                    </small>
                </div>
            </section>

            <section
                className="statistics-print-section statistics-print-current-week"
                data-testid="statistics-print-current-week"
            >
                <div className="statistics-print-section-heading">
                    <h2>Aktuelle Wochenübersicht</h2>
                    <strong>
                        {stats.weekly.totalEntries}{' '}
                        {stats.weekly.totalEntries === 1
                            ? 'Beratung'
                            : 'Beratungen'}
                    </strong>
                </div>
                <div className="statistics-print-days">
                    {stats.weekly.days.map((day) => (
                        <div key={day.date}>
                            <span>{day.label}</span>
                            <strong>{day.entries}</strong>
                            <small>{day.date.slice(5)}</small>
                            <div className="statistics-print-bar">
                                <span
                                    style={{
                                        width: `${percentage(day.entries, maxWeeklyEntries)}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {stats.weekly.semesterWeeks.length > 0 && (
                <section className="statistics-print-section statistics-print-semester-weeks">
                    <h2>Semesterwochen</h2>
                    <div>
                        {stats.weekly.semesterWeeks.map((semesterWeek) => (
                            <p key={semesterWeek.start}>
                                <span>{semesterWeek.label}</span>
                                <small>{semesterWeek.rangeLabel}</small>
                                <strong>{semesterWeek.entries}</strong>
                            </p>
                        ))}
                    </div>
                </section>
            )}

            <div
                className="statistics-print-breakdowns"
                data-testid="statistics-print-breakdowns"
            >
                <PrintBreakdown
                    title="Nach Fachbereich"
                    items={stats.faculties}
                />
                <PrintBreakdown
                    title="Nach Studiengang"
                    items={stats.degrees}
                />
                <PrintBreakdown title="Nach Thema" items={stats.topics} />
            </div>
        </article>
    );
}

export default function AdminStatisticsIndex({
    filters,
    week,
    formOptions,
    stats,
}: Props) {
    const hasActiveFilters = Object.values(filters).some(
        (value) => value !== '',
    );
    const activeFilterCount = Object.values(filters).filter(
        (value) => value !== '',
    ).length;
    const filterLabels = activeFilterLabels(filters, formOptions);
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const filterForm = useForm<Filters>(filters);
    const maxWeeklyEntries = Math.max(
        ...stats.weekly.days.map((day) => day.entries),
        0,
    );
    const maxSemesterWeekEntries = Math.max(
        ...stats.weekly.semesterWeeks.map(
            (semesterWeek) => semesterWeek.entries,
        ),
        10,
    );
    const maxFacultyEntries = Math.max(
        ...stats.faculties.map((faculty) => faculty.entries),
        0,
    );
    const maxDegreeEntries = Math.max(
        ...stats.degrees.map((degree) => degree.entries),
        0,
    );
    const maxTopicEntries = Math.max(
        ...stats.topics.map((topic) => topic.entries),
        0,
    );

    function statisticsQuery(
        overrides: Partial<Filters> & { week?: string } = {},
    ): Record<string, string> {
        return cleanFilters({ ...filters, week, ...overrides });
    }

    function applyFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        router.visit(
            adminStatisticsIndex({ query: statisticsQuery(filterForm.data) }),
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    function resetFilters(): void {
        const clearedFilters = {
            user: '',
            semester: '',
            degree: '',
            faculty: '',
            topic: '',
            online: '',
            from: '',
            until: '',
        };

        filterForm.setData(clearedFilters);

        router.visit(adminStatisticsIndex({ query: { week } }), {
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

    function changeWeek(targetWeek: string): void {
        router.visit(
            adminStatisticsIndex({
                query: statisticsQuery({ week: targetWeek }),
            }),
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="Statistik" />
            <PrintStatistics
                filters={filters}
                week={week}
                formOptions={formOptions}
                stats={stats}
            />
            <div className="flex flex-col gap-5 px-4 py-5 md:px-6 md:py-6 print:hidden">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Statistik
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Verdichte alle Helpdesk-Einsätze zu Wochen-,
                            Fachbereichs-, Studiengangs- und Themenstatistiken.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2 print:hidden">
                        <Button asChild variant="outline">
                            <a
                                href={adminStatisticsCsv.url({
                                    query: cleanFilters(filters),
                                })}
                            >
                                <Download className="size-4" />
                                Als CSV herunterladen
                            </a>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.print()}
                        >
                            <Printer className="size-4" />
                            Als PDF exportieren
                        </Button>
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
                                className={`size-4 transition-transform ${isFilterOpen ? 'rotate-180' : ''}`}
                            />
                        </Button>
                    </div>
                </div>

                {hasActiveFilters && (
                    <div className="text-sm text-muted-foreground">
                        <span className="font-medium">Aktive Filter:</span>{' '}
                        {filterLabels.join(' · ')}
                    </div>
                )}

                <Collapsible
                    open={isFilterOpen}
                    onOpenChange={setIsFilterOpen}
                    className="print:hidden"
                >
                    <CollapsibleContent>
                        <div className="rounded-xl border bg-muted/20 p-4 md:p-5">
                            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Filtere die Statistik nach Tutor, Zeitraum,
                                    Semester oder Themenbereich.
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
                                            <SelectTrigger
                                                id="statistics-semester-filter"
                                                className="w-full"
                                            >
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

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardDescription>Beratungen</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.totals.entries}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {stats.totals.visitors} Besucher:innen im aktuellen
                            Filter.
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardDescription>Stunden</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.totals.hours}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock3 className="size-4" />
                            <span>
                                {stats.totals.minutes} Minuten Gesamtzeit.
                            </span>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardDescription>
                                Aktive Tutor:innen
                            </CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.totals.activeTutors}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Users className="size-4" />
                            <span>
                                {stats.totals.semesters} Semester im Filter.
                            </span>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardDescription>Online-Anteil</CardDescription>
                            <CardTitle className="text-3xl">
                                {stats.totals.onlinePercentage}%
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                            <MonitorSmartphone className="size-4" />
                            <span>
                                {stats.totals.onlineEntries} online,{' '}
                                {stats.totals.presenceEntries} präsent.
                            </span>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="gap-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <BarChart3 className="size-4 text-muted-foreground" />
                                    <CardTitle>Wochenübersicht</CardTitle>
                                </div>
                                <CardDescription>
                                    Anzahl der Beratungen pro Woche.
                                </CardDescription>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="print:hidden"
                                    onClick={() =>
                                        changeWeek(stats.weekly.previous)
                                    }
                                >
                                    <ChevronLeft className="size-4" />
                                </Button>
                                <div className="min-w-44 rounded-lg border px-3 py-2 text-center">
                                    <p className="text-sm font-medium">
                                        {stats.weekly.label}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {stats.weekly.rangeLabel}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="print:hidden"
                                    onClick={() =>
                                        changeWeek(stats.weekly.next)
                                    }
                                >
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {stats.weekly.totalEntries}{' '}
                                {stats.weekly.totalEntries === 1
                                    ? 'Beratung'
                                    : 'Beratungen'}{' '}
                                in dieser Woche
                            </Badge>
                        </div>
                        <div className="grid gap-3 md:grid-cols-7">
                            {stats.weekly.days.map((day) => (
                                <div
                                    key={day.date}
                                    className="rounded-xl border bg-muted/20 p-4"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-medium">
                                                {day.label}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {day.date}
                                            </p>
                                        </div>
                                        <span className="text-lg font-semibold">
                                            {day.entries}
                                        </span>
                                    </div>
                                    <div className="h-2 rounded-full bg-border">
                                        <div
                                            className="h-2 rounded-full bg-foreground/80"
                                            style={{
                                                width: `${percentage(day.entries, Math.max(maxWeeklyEntries, 10))}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                        {stats.weekly.semesterWeeks.length > 0 && (
                            <div className="space-y-3 border-t pt-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Alle bisherigen Semesterwochen
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Neueste Woche zuerst, einschließlich
                                        Wochen ohne Beratungen.
                                    </p>
                                </div>
                                <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                    {stats.weekly.semesterWeeks.map(
                                        (semesterWeek) => (
                                            <button
                                                key={semesterWeek.start}
                                                type="button"
                                                className="rounded-lg border p-3 text-left transition-colors hover:bg-muted/40 print:break-inside-avoid"
                                                onClick={() =>
                                                    changeWeek(
                                                        semesterWeek.start,
                                                    )
                                                }
                                            >
                                                <div className="mb-2 flex items-center justify-between gap-3">
                                                    <span className="text-sm font-medium">
                                                        {semesterWeek.label}
                                                    </span>
                                                    <span className="text-sm font-semibold">
                                                        {semesterWeek.entries}
                                                    </span>
                                                </div>
                                                <p className="mb-2 text-xs text-muted-foreground">
                                                    {semesterWeek.rangeLabel}
                                                </p>
                                                <div className="h-2 rounded-full bg-border">
                                                    <div
                                                        className="h-2 rounded-full bg-foreground/80"
                                                        style={{
                                                            width: `${percentage(
                                                                semesterWeek.entries,
                                                                maxSemesterWeekEntries,
                                                            )}%`,
                                                        }}
                                                    />
                                                </div>
                                            </button>
                                        ),
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Layers3 className="size-4 text-muted-foreground" />
                                <CardTitle>Verteilung Fachbereich</CardTitle>
                            </div>
                            <CardDescription>
                                Beratungen nach Fachbereich.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {stats.faculties.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Keine Fachbereiche für diese Auswahl
                                    vorhanden.
                                </p>
                            ) : (
                                stats.faculties.map((faculty) => (
                                    <div
                                        key={faculty.label}
                                        className="space-y-2"
                                    >
                                        <div className="flex items-center justify-between text-sm">
                                            <span>{faculty.label}</span>
                                            <span className="font-medium">
                                                {faculty.entries}
                                            </span>
                                        </div>
                                        <div className="h-2 rounded-full bg-border">
                                            <div
                                                className="h-2 rounded-full bg-foreground/80"
                                                style={{
                                                    width: `${percentage(faculty.entries, maxFacultyEntries)}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <GraduationCap className="size-4 text-muted-foreground" />
                                <CardTitle>Verteilung Studiengang</CardTitle>
                            </div>
                            <CardDescription>
                                Beratungen nach Studiengang.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {stats.degrees.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Keine Studiengänge für diese Auswahl
                                    vorhanden.
                                </p>
                            ) : (
                                stats.degrees.map((degree) => (
                                    <div
                                        key={degree.label}
                                        className="space-y-2"
                                    >
                                        <div className="flex items-center justify-between text-sm">
                                            <span>{degree.label}</span>
                                            <span className="font-medium">
                                                {degree.entries}
                                            </span>
                                        </div>
                                        <div className="h-2 rounded-full bg-border">
                                            <div
                                                className="h-2 rounded-full bg-foreground/80"
                                                style={{
                                                    width: `${percentage(degree.entries, maxDegreeEntries)}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <School className="size-4 text-muted-foreground" />
                                <CardTitle>Verteilung Thema</CardTitle>
                            </div>
                            <CardDescription>
                                Beratungen nach Thema.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {stats.topics.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Keine Themen für diese Auswahl vorhanden.
                                </p>
                            ) : (
                                stats.topics.map((topic) => (
                                    <div
                                        key={topic.label}
                                        className="space-y-2"
                                    >
                                        <div className="flex items-center justify-between text-sm">
                                            <span>{topic.label}</span>
                                            <span className="font-medium">
                                                {topic.entries}
                                            </span>
                                        </div>
                                        <div className="h-2 rounded-full bg-border">
                                            <div
                                                className="h-2 rounded-full bg-foreground/80"
                                                style={{
                                                    width: `${percentage(topic.entries, maxTopicEntries)}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminStatisticsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Statistik',
            href: adminStatisticsIndex(),
        },
    ],
};
