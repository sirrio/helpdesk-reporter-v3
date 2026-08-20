import { Head, router, useForm } from '@inertiajs/react';
import {
    Mail,
    PencilLine,
    Plus,
    RotateCcw,
    Search,
    UserRound,
    Users,
    UserX,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    destroy as destroyAdminUser,
    index as adminUsersIndex,
    restore as restoreAdminUser,
    store as storeAdminUser,
    update as updateAdminUser,
} from '@/actions/App/Http/Controllers/AdminUserController';
import { UserAutomationDialog } from '@/components/admin/user-automation-dialog';
import type { UserAutomation } from '@/components/admin/user-automation-dialog';
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

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    isMod: boolean;
    isAdmin: boolean;
    emailVerifiedAt: string | null;
    createdAt: string | null;
    attendancesCount: number;
    isCurrentUser: boolean;
    deletedAt: string | null;
    anonymizedAt: string | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { search: string; role: string; status: string };
type Props = {
    users: { data: ManagedUser[]; links: PaginationLink[] };
    filters: Filters;
    automation: UserAutomation;
};

const roleOptions = [
    { value: FILTER_ALL, label: 'Alle Rollen' },
    { value: 'admin', label: 'Admins' },
    { value: 'mod', label: 'Moderator:innen' },
    { value: 'tutor', label: 'Tutor:innen' },
];

const statusOptions = [
    { value: FILTER_ALL, label: 'Alle Status' },
    { value: 'active', label: 'Aktiv' },
    { value: 'deactivated', label: 'Deaktiviert' },
    { value: 'anonymized', label: 'Anonymisiert' },
];

function cleanUserFilters(filters: Filters): Record<string, string> {
    return cleanFilters({
        search: filters.search,
        role: filters.role,
        status: filters.status,
    });
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Unbekannt';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
    }).format(new Date(value));
}

function roleLabels(user: ManagedUser): string[] {
    if (user.isAdmin) {
        return ['Admin', 'Moderator'];
    }

    if (user.isMod) {
        return ['Moderator'];
    }

    return ['Tutor'];
}

export default function AdminUsersIndex({ users, filters, automation }: Props) {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<ManagedUser | null>(null);
    const [deactivatingUser, setDeactivatingUser] =
        useState<ManagedUser | null>(null);
    const [isStatusActionProcessing, setIsStatusActionProcessing] =
        useState(false);
    const filterForm = useForm<Filters>(filters);
    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        isMod: false,
        isAdmin: false,
    });
    const editForm = useForm({
        name: '',
        email: '',
        password: '',
        isMod: false,
        isAdmin: false,
    });

    function applyFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        router.visit(
            adminUsersIndex({ query: cleanUserFilters(filterForm.data) }),
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    function resetFilters(): void {
        filterForm.setData({ search: '', role: '', status: '' });
        router.visit(adminUsersIndex(), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function submitCreate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        createForm.submit(storeAdminUser(), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setIsCreateDialogOpen(false);
            },
            onError: () => {
                // errors shown inline
            },
        });
    }

    function openEditDialog(user: ManagedUser): void {
        setEditingUser(user);
        editForm.setData({
            name: user.name,
            email: user.email,
            password: '',
            isMod: user.isMod,
            isAdmin: user.isAdmin,
        });
        editForm.clearErrors();
    }

    function submitUpdate(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        if (!editingUser) {
            return;
        }

        editForm.submit(updateAdminUser(editingUser.id), {
            preserveScroll: true,
            onSuccess: () => {
                setEditingUser(null);
                editForm.reset('password');
            },
            onError: () => {
                // errors shown inline
            },
        });
    }

    function setCreateRoles(isMod: boolean, isAdmin: boolean): void {
        createForm.setData({ ...createForm.data, isMod, isAdmin });
    }

    function setEditRoles(isMod: boolean, isAdmin: boolean): void {
        editForm.setData({ ...editForm.data, isMod, isAdmin });
    }

    function deactivateUser(): void {
        if (!deactivatingUser) {
            return;
        }

        router.visit(destroyAdminUser(deactivatingUser.id), {
            method: 'delete',
            preserveScroll: true,
            onStart: () => setIsStatusActionProcessing(true),
            onSuccess: () => setDeactivatingUser(null),
            onFinish: () => setIsStatusActionProcessing(false),
        });
    }

    function reactivateUser(user: ManagedUser): void {
        router.visit(restoreAdminUser(user.id), {
            method: 'patch',
            preserveScroll: true,
            onStart: () => setIsStatusActionProcessing(true),
            onFinish: () => setIsStatusActionProcessing(false),
        });
    }

    return (
        <>
            <Head title="Benutzerverwaltung" />
            <div className="flex flex-col gap-5 px-4 py-5 md:px-6 md:py-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Benutzerverwaltung
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Verwalte Zugänge für Tutor:innen, Moderator:innen
                            und Admins.
                        </p>
                        <p className="max-w-3xl text-xs text-muted-foreground">
                            Moderator:innen können alle Einträge und Statistiken
                            einsehen und Einträge korrigieren. Admins verwalten
                            zusätzlich Benutzer:innen und Stammdaten.
                            „Verifiziert“ bedeutet, dass die E-Mail-Adresse
                            bestätigt wurde; administrativ angelegte Konten
                            gelten direkt als bestätigt. Deaktivierte Accounts
                            können sich nicht mehr anmelden; ihre bisherigen
                            Einträge bleiben erhalten.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <UserAutomationDialog automation={automation} />
                        <Dialog
                            open={isCreateDialogOpen}
                            onOpenChange={setIsCreateDialogOpen}
                        >
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="size-4" />
                                    Neuer Benutzer
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>Benutzer anlegen</DialogTitle>
                                    <DialogDescription>
                                        Neue Accounts werden direkt verifiziert
                                        angelegt.
                                    </DialogDescription>
                                </DialogHeader>
                                <form
                                    onSubmit={submitCreate}
                                    className="space-y-5"
                                >
                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="create-name">
                                                Name
                                            </Label>
                                            <Input
                                                id="create-name"
                                                value={createForm.data.name}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={createForm.errors.name}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="create-email">
                                                E-Mail
                                            </Label>
                                            <Input
                                                id="create-email"
                                                type="email"
                                                value={createForm.data.email}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'email',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.email
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="create-password">
                                                Passwort
                                            </Label>
                                            <Input
                                                id="create-password"
                                                type="password"
                                                value={createForm.data.password}
                                                onChange={(event) =>
                                                    createForm.setData(
                                                        'password',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    createForm.errors.password
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-3">
                                        <Label>Rollen</Label>
                                        <label className="flex items-start gap-3 rounded-lg border p-3">
                                            <Checkbox
                                                checked={createForm.data.isMod}
                                                onCheckedChange={(checked) =>
                                                    setCreateRoles(
                                                        checked === true,
                                                        checked === true
                                                            ? createForm.data
                                                                  .isAdmin
                                                            : false,
                                                    )
                                                }
                                            />
                                            <span>
                                                <span className="block text-sm font-medium">
                                                    Moderator:in
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    Darf alle Einträge und
                                                    Statistiken ansehen und
                                                    korrigieren.
                                                </span>
                                            </span>
                                        </label>
                                        <label className="flex items-start gap-3 rounded-lg border p-3">
                                            <Checkbox
                                                checked={
                                                    createForm.data.isAdmin
                                                }
                                                onCheckedChange={(checked) =>
                                                    setCreateRoles(
                                                        checked === true
                                                            ? true
                                                            : createForm.data
                                                                  .isMod,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span className="text-sm font-medium">
                                                Admin
                                            </span>
                                        </label>
                                        <InputError
                                            message={
                                                createForm.errors.isAdmin ??
                                                createForm.errors.isMod
                                            }
                                        />
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <Button
                                            type="submit"
                                            disabled={createForm.processing}
                                        >
                                            {createForm.processing && (
                                                <Spinner className="mr-2" />
                                            )}
                                            Benutzer speichern
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
                    <CardHeader className="gap-4">
                        <div className="flex items-center gap-2">
                            <Users className="size-4 text-muted-foreground" />
                            <CardTitle>Benutzer</CardTitle>
                        </div>
                        <CardDescription>
                            Suche Accounts, passe Rollen oder Zugangsdaten an
                            und steuere den Zugang.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <form
                            onSubmit={applyFilters}
                            className="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_14rem_14rem_auto_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="search">Suche</Label>
                                <div className="relative">
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        className="pl-9"
                                        placeholder="Name oder E-Mail"
                                        value={filterForm.data.search}
                                        onChange={(event) =>
                                            filterForm.setData(
                                                'search',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Rolle</Label>
                                <Select
                                    value={filterForm.data.role || FILTER_ALL}
                                    onValueChange={(value) =>
                                        filterForm.setData(
                                            'role',
                                            value === FILTER_ALL ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Alle Rollen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roleOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={filterForm.data.status || FILTER_ALL}
                                    onValueChange={(value) =>
                                        filterForm.setData(
                                            'status',
                                            value === FILTER_ALL ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Alle Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statusOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button type="submit" className="w-full">
                                    Anwenden
                                </Button>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="w-full"
                                    onClick={resetFilters}
                                >
                                    Zurücksetzen
                                </Button>
                            </div>
                        </form>

                        {users.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed px-6 py-10 text-center">
                                <p className="text-sm font-medium">
                                    Keine Benutzer für diese Filter gefunden.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {users.data.map((user) => (
                                    <article
                                        key={user.id}
                                        className={`rounded-xl border p-4 ${user.deletedAt ? 'bg-muted/40' : 'bg-muted/20'}`}
                                    >
                                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div className="space-y-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <div className="flex items-center gap-2 text-sm font-medium">
                                                        <UserRound className="size-4 text-muted-foreground" />
                                                        <span>{user.name}</span>
                                                    </div>
                                                    {user.isCurrentUser && (
                                                        <Badge variant="secondary">
                                                            Du
                                                        </Badge>
                                                    )}
                                                    <Badge
                                                        variant={
                                                            user.anonymizedAt
                                                                ? 'outline'
                                                                : user.deletedAt
                                                                  ? 'outline'
                                                                  : 'secondary'
                                                        }
                                                    >
                                                        {user.anonymizedAt
                                                            ? 'Anonymisiert'
                                                            : user.deletedAt
                                                              ? 'Deaktiviert'
                                                              : 'Aktiv'}
                                                    </Badge>
                                                    {!user.anonymizedAt &&
                                                        roleLabels(user).map(
                                                            (role) => (
                                                                <Badge
                                                                    key={`${user.id}-${role}`}
                                                                    variant={
                                                                        role ===
                                                                        'Admin'
                                                                            ? 'default'
                                                                            : role ===
                                                                                'Moderator'
                                                                              ? 'secondary'
                                                                              : 'outline'
                                                                    }
                                                                >
                                                                    {role}
                                                                </Badge>
                                                            ),
                                                        )}
                                                </div>
                                                <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-3">
                                                    <div className="flex items-center gap-2">
                                                        <Mail className="size-4" />
                                                        <span>
                                                            {user.email}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span>
                                                            {
                                                                user.attendancesCount
                                                            }{' '}
                                                            Einsätze
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span>
                                                            {user.emailVerifiedAt
                                                                ? 'Verifiziert'
                                                                : 'Unverifiziert'}
                                                        </span>
                                                    </div>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    Angelegt am{' '}
                                                    {formatDate(user.createdAt)}
                                                </p>
                                                {user.deletedAt && (
                                                    <p className="text-sm text-muted-foreground">
                                                        Deaktiviert am{' '}
                                                        {formatDate(
                                                            user.deletedAt,
                                                        )}
                                                    </p>
                                                )}
                                                {user.anonymizedAt && (
                                                    <p className="text-sm text-muted-foreground">
                                                        Anonymisiert am{' '}
                                                        {formatDate(
                                                            user.anonymizedAt,
                                                        )}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {!user.deletedAt && (
                                                    <>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                openEditDialog(
                                                                    user,
                                                                )
                                                            }
                                                        >
                                                            <PencilLine className="size-4" />
                                                            Bearbeiten
                                                        </Button>
                                                        {!user.isCurrentUser && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setDeactivatingUser(
                                                                        user,
                                                                    )
                                                                }
                                                            >
                                                                <UserX className="size-4" />
                                                                Deaktivieren
                                                            </Button>
                                                        )}
                                                    </>
                                                )}
                                                {user.deletedAt &&
                                                    !user.anonymizedAt && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            disabled={
                                                                isStatusActionProcessing
                                                            }
                                                            onClick={() =>
                                                                reactivateUser(
                                                                    user,
                                                                )
                                                            }
                                                        >
                                                            <RotateCcw className="size-4" />
                                                            Reaktivieren
                                                        </Button>
                                                    )}
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}

                        <PaginationLinks links={users.links} />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={editingUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingUser(null);
                        editForm.clearErrors();
                        editForm.reset('password');
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Benutzer bearbeiten</DialogTitle>
                        <DialogDescription>
                            Passe Stammdaten, Passwort und Rollen an.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitUpdate} className="space-y-5">
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-name">Name</Label>
                                <Input
                                    id="edit-name"
                                    value={editForm.data.name}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={editForm.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-email">E-Mail</Label>
                                <Input
                                    id="edit-email"
                                    type="email"
                                    value={editForm.data.email}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={editForm.errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-password">
                                    Neues Passwort
                                </Label>
                                <Input
                                    id="edit-password"
                                    type="password"
                                    value={editForm.data.password}
                                    onChange={(event) =>
                                        editForm.setData(
                                            'password',
                                            event.target.value,
                                        )
                                    }
                                />
                                <p className="text-sm text-muted-foreground">
                                    Leer lassen, wenn es unverändert bleiben
                                    soll.
                                </p>
                                <InputError
                                    message={editForm.errors.password}
                                />
                            </div>
                        </div>
                        <div className="grid gap-3">
                            <Label>Rollen</Label>
                            <label className="flex items-start gap-3 rounded-lg border p-3">
                                <Checkbox
                                    checked={editForm.data.isMod}
                                    onCheckedChange={(checked) =>
                                        setEditRoles(
                                            checked === true,
                                            checked === true
                                                ? editForm.data.isAdmin
                                                : false,
                                        )
                                    }
                                />
                                <span className="text-sm font-medium">
                                    Moderator:in
                                </span>
                            </label>
                            <label className="flex items-start gap-3 rounded-lg border p-3">
                                <Checkbox
                                    checked={editForm.data.isAdmin}
                                    onCheckedChange={(checked) =>
                                        setEditRoles(
                                            checked === true
                                                ? true
                                                : editForm.data.isMod,
                                            checked === true,
                                        )
                                    }
                                />
                                <span className="text-sm font-medium">
                                    Admin
                                </span>
                            </label>
                            <InputError
                                message={
                                    editForm.errors.isAdmin ??
                                    editForm.errors.isMod
                                }
                            />
                        </div>
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
                                onClick={() => setEditingUser(null)}
                            >
                                Abbrechen
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deactivatingUser !== null}
                onOpenChange={(open) => {
                    if (!open && !isStatusActionProcessing) {
                        setDeactivatingUser(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Benutzer deaktivieren?</DialogTitle>
                        <DialogDescription>
                            {deactivatingUser?.name} kann sich danach nicht mehr
                            anmelden. Bestehende Einsätze und deren Zuordnung
                            bleiben erhalten. Der Account kann jederzeit
                            reaktiviert werden.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={isStatusActionProcessing}
                            onClick={() => setDeactivatingUser(null)}
                        >
                            Abbrechen
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={isStatusActionProcessing}
                            onClick={deactivateUser}
                        >
                            {isStatusActionProcessing && (
                                <Spinner className="mr-2" />
                            )}
                            Deaktivieren
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminUsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Benutzer',
            href: adminUsersIndex(),
        },
    ],
};
