import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    CalendarRange,
    ClipboardList,
    GraduationCap,
    Shield,
    Users,
} from 'lucide-react';
import { index as adminDegrees } from '@/actions/App/Http/Controllers/AdminDegreeController';
import { index as adminFaculties } from '@/actions/App/Http/Controllers/AdminFacultyController';
import { index as adminSemesters } from '@/actions/App/Http/Controllers/AdminSemesterController';
import { index as adminUsers } from '@/actions/App/Http/Controllers/AdminUserController';
import {
    adminIndex as adminAttendances,
    index as attendances,
    statistics as adminStatistics,
} from '@/actions/App/Http/Controllers/AttendanceController';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const currentSemester = new URL(
        page.url,
        typeof window !== 'undefined'
            ? window.location.origin
            : 'http://localhost',
    ).searchParams.get('semester');

    const mainNavItems: NavItem[] = [
        {
            title: 'Anwesenheiten',
            href: attendances(),
            icon: ClipboardList,
            children: page.props.navigation.attendanceSemesters.map(
                (semester) => ({
                    title: semester,
                    href: attendances({ query: { semester } }),
                    isActive: currentSemester === semester,
                }),
            ),
        },
    ];

    if (page.props.auth.user.isMod || page.props.auth.user.isAdmin) {
        mainNavItems.push({
            title: 'Alle Einsätze',
            href: adminAttendances(),
            icon: Shield,
        });
        mainNavItems.push({
            title: 'Statistik',
            href: adminStatistics(),
            icon: BarChart3,
        });
    }

    if (page.props.auth.user.isAdmin) {
        mainNavItems.push({
            title: 'Benutzer',
            href: adminUsers(),
            icon: Users,
        });
        mainNavItems.push({
            title: 'Semester',
            href: adminSemesters(),
            icon: CalendarRange,
        });
        mainNavItems.push({
            title: 'Studiengänge',
            href: adminDegrees(),
            icon: GraduationCap,
        });
        mainNavItems.push({
            title: 'Fachbereiche',
            href: adminFaculties(),
            icon: Building2,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset" className="print:hidden">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={attendances()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
