export function cleanFilters<T extends Record<string, string>>(filters: T): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );
}
