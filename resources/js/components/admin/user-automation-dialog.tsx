import { router, useForm } from '@inertiajs/react';
import { Activity, Clock3, Play } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import runUserAutomationCheck from '@/actions/App/Http/Controllers/AdminUserAutomationCheckController';
import updateUserAutomation from '@/actions/App/Http/Controllers/AdminUserAutomationController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Spinner } from '@/components/ui/spinner';

type AutomationHealth = {
    status: 'healthy' | 'stale' | 'unknown';
    lastSeenAt: string | null;
};

type AutomationCheck = {
    status: 'idle' | 'pending' | 'passed' | 'failed';
    requestedAt: string | null;
    completedAt: string | null;
};

export type UserAutomation = {
    anonymizationMonths: number;
    pendingAnonymizationCount: number;
    scheduler: AutomationHealth;
    queue: AutomationHealth & { connection: string };
    healthCheck: AutomationCheck;
};

function healthLabel(status: AutomationHealth['status']): string {
    if (status === 'healthy') {
        return 'Läuft';
    }

    if (status === 'stale') {
        return 'Überfällig';
    }

    return 'Noch kein Signal';
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Noch nie';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function checkLabel(status: AutomationCheck['status']): string {
    if (status === 'passed') {
        return 'Erfolgreich';
    }

    if (status === 'pending') {
        return 'Test läuft';
    }

    if (status === 'failed') {
        return 'Fehlgeschlagen';
    }

    return 'Nicht getestet';
}

function checkDescription(status: AutomationCheck['status']): string {
    if (status === 'passed') {
        return 'Der echte Scheduler hat die Anfrage aufgenommen und die echte Queue hat sie verarbeitet.';
    }

    if (status === 'pending') {
        return 'Die Anfrage wartet auf den nächsten Schedulerlauf und die anschließende Verarbeitung durch die Queue.';
    }

    if (status === 'failed') {
        return 'Scheduler und Queue haben die Anfrage nicht innerhalb von fünf Minuten vollständig verarbeitet.';
    }

    return 'Starte einen Ende-zu-Ende-Test von Scheduler und Queue.';
}

export function UserAutomationDialog({
    automation,
}: {
    automation: UserAutomation;
}) {
    const [isOpen, setIsOpen] = useState(false);
    const settingsForm = useForm({
        anonymizationMonths: automation.anonymizationMonths.toString(),
    });
    const checkForm = useForm({});
    const isHealthy =
        automation.scheduler.status === 'healthy' &&
        automation.queue.status === 'healthy';
    const hasWarning =
        automation.scheduler.status === 'stale' ||
        automation.queue.status === 'stale';

    useEffect(() => {
        if (!isOpen || automation.healthCheck.status !== 'pending') {
            return;
        }

        const interval = window.setInterval(() => {
            router.reload({
                only: ['automation'],
                preserveErrors: true,
            });
        }, 3000);

        return () => window.clearInterval(interval);
    }, [automation.healthCheck.status, isOpen]);

    function submitSettings(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        settingsForm.submit(updateUserAutomation(), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => settingsForm.setDefaults(),
        });
    }

    function runHealthCheck(): void {
        checkForm.submit(runUserAutomationCheck(), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline">
                    <span
                        className={`size-2 rounded-full ${
                            isHealthy
                                ? 'bg-emerald-500'
                                : hasWarning
                                  ? 'bg-destructive'
                                  : 'bg-muted-foreground/50'
                        }`}
                        aria-hidden="true"
                    />
                    Automatisierung
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Automatisierung</DialogTitle>
                    <DialogDescription>
                        Verwalte die Anonymisierungsfrist und prüfe den echten
                        Scheduler- und Queue-Ablauf.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-5">
                    <section className="grid gap-4 rounded-xl border p-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="space-y-1">
                                <h3 className="text-sm font-medium">
                                    Scheduler und Queue testen
                                </h3>
                                <p className="max-w-xl text-xs text-muted-foreground">
                                    {checkDescription(
                                        automation.healthCheck.status,
                                    )}
                                </p>
                            </div>
                            <Badge
                                variant={
                                    automation.healthCheck.status === 'passed'
                                        ? 'secondary'
                                        : automation.healthCheck.status ===
                                            'failed'
                                          ? 'destructive'
                                          : 'outline'
                                }
                            >
                                {checkLabel(automation.healthCheck.status)}
                            </Badge>
                        </div>
                        {automation.healthCheck.requestedAt && (
                            <p className="text-xs text-muted-foreground">
                                Gestartet:{' '}
                                {formatDateTime(
                                    automation.healthCheck.requestedAt,
                                )}
                                {automation.healthCheck.completedAt && (
                                    <>
                                        {' · '}Bestätigt:{' '}
                                        {formatDateTime(
                                            automation.healthCheck.completedAt,
                                        )}
                                    </>
                                )}
                            </p>
                        )}
                        <div>
                            <Button
                                type="button"
                                onClick={runHealthCheck}
                                disabled={
                                    checkForm.processing ||
                                    automation.healthCheck.status === 'pending'
                                }
                            >
                                {checkForm.processing ||
                                automation.healthCheck.status === 'pending' ? (
                                    <Spinner className="mr-2" />
                                ) : (
                                    <Play className="size-4" />
                                )}
                                {automation.healthCheck.status === 'pending'
                                    ? 'Test läuft'
                                    : 'Jetzt testen'}
                            </Button>
                        </div>
                    </section>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <section className="rounded-xl border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-2 text-sm font-medium">
                                    <Activity className="size-4 text-muted-foreground" />
                                    Scheduler
                                </div>
                                <Badge
                                    variant={
                                        automation.scheduler.status ===
                                        'healthy'
                                            ? 'secondary'
                                            : automation.scheduler.status ===
                                                'stale'
                                              ? 'destructive'
                                              : 'outline'
                                    }
                                >
                                    {healthLabel(automation.scheduler.status)}
                                </Badge>
                            </div>
                            <p className="mt-2 text-xs text-muted-foreground">
                                Letztes Signal:{' '}
                                {formatDateTime(
                                    automation.scheduler.lastSeenAt,
                                )}
                            </p>
                        </section>
                        <section className="rounded-xl border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-2 text-sm font-medium">
                                    <Activity className="size-4 text-muted-foreground" />
                                    Queue
                                </div>
                                <Badge
                                    variant={
                                        automation.queue.status === 'healthy'
                                            ? 'secondary'
                                            : automation.queue.status ===
                                                'stale'
                                              ? 'destructive'
                                              : 'outline'
                                    }
                                >
                                    {healthLabel(automation.queue.status)}
                                </Badge>
                            </div>
                            <p className="mt-2 text-xs text-muted-foreground">
                                Verbindung: {automation.queue.connection};
                                letztes Signal:{' '}
                                {formatDateTime(automation.queue.lastSeenAt)}
                            </p>
                        </section>
                    </div>

                    <section className="grid gap-4 rounded-xl border p-4">
                        <div className="flex items-center gap-2">
                            <Clock3 className="size-4 text-muted-foreground" />
                            <h3 className="text-sm font-medium">
                                Automatische Anonymisierung
                            </h3>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Nach Ablauf der Frist werden personenbezogene
                            Accountdaten irreversibel entfernt. Beratungen und
                            Statistiken bleiben ohne Personenbezug erhalten.
                        </p>
                        <form
                            onSubmit={submitSettings}
                            className="grid gap-3 sm:grid-cols-[minmax(0,12rem)_auto] sm:items-end"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="anonymization-months">
                                    Frist nach Deaktivierung
                                </Label>
                                <div className="flex items-center gap-3">
                                    <Input
                                        id="anonymization-months"
                                        type="number"
                                        min="1"
                                        max="120"
                                        inputMode="numeric"
                                        value={
                                            settingsForm.data
                                                .anonymizationMonths
                                        }
                                        onChange={(event) =>
                                            settingsForm.setData(
                                                'anonymizationMonths',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <span className="text-sm text-muted-foreground">
                                        Monate
                                    </span>
                                </div>
                                <InputError
                                    message={
                                        settingsForm.errors.anonymizationMonths
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={settingsForm.processing}
                            >
                                {settingsForm.processing && (
                                    <Spinner className="mr-2" />
                                )}
                                Frist speichern
                            </Button>
                        </form>
                        <p className="text-xs text-muted-foreground">
                            Zulässig sind 1 bis 120 Monate. Eine Verkürzung
                            greift beim nächsten stündlichen Prüflauf.{' '}
                            {automation.pendingAnonymizationCount === 0
                                ? 'Aktuell wartet kein Account auf die Anonymisierung.'
                                : `${automation.pendingAnonymizationCount} Account(s) warten bereits auf die Anonymisierung.`}
                        </p>
                    </section>
                </div>
            </DialogContent>
        </Dialog>
    );
}
