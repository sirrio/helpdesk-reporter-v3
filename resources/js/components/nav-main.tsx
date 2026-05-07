import { Link } from '@inertiajs/react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Helpdesk</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const hasChildren =
                        item.children !== undefined && item.children.length > 0;
                    const isItemActive =
                        item.isActive ??
                        (isCurrentUrl(item.href) ||
                            item.children?.some((child) => child.isActive) ||
                            false);

                    if (hasChildren && isCollapsed) {
                        return (
                            <SidebarMenuItem key={item.title}>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <SidebarMenuButton
                                            isActive={isItemActive}
                                            tooltip={{ children: item.title }}
                                            className="data-[state=open]:bg-sidebar-accent"
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </SidebarMenuButton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        side="right"
                                        align="start"
                                        className="min-w-56 rounded-lg"
                                    >
                                        <DropdownMenuLabel>
                                            {item.title}
                                        </DropdownMenuLabel>
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={item.href}
                                                prefetch
                                                className="flex w-full items-center gap-2"
                                            >
                                                {item.icon && <item.icon />}
                                                <span>Alle Einträge</span>
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        {item.children?.map((child) => (
                                            <DropdownMenuItem
                                                key={`${item.title}-${child.title}`}
                                                asChild
                                                className={cn(
                                                    child.isActive &&
                                                        'bg-accent text-accent-foreground',
                                                )}
                                            >
                                                <Link
                                                    href={child.href}
                                                    prefetch
                                                    className="flex w-full items-center"
                                                >
                                                    <span>{child.title}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </SidebarMenuItem>
                        );
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isItemActive}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                            {hasChildren && (
                                <SidebarMenuSub>
                                    {item.children?.map((child) => (
                                        <SidebarMenuSubItem
                                            key={`${item.title}-${child.title}`}
                                        >
                                            <SidebarMenuSubButton
                                                asChild
                                                isActive={child.isActive}
                                            >
                                                <Link href={child.href} prefetch>
                                                    <span>{child.title}</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    ))}
                                </SidebarMenuSub>
                            )}
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
