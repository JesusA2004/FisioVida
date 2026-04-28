const dateFormatter = new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' });
const dateTimeFormatter = new Intl.DateTimeFormat('es-MX', {
    dateStyle: 'long',
    timeStyle: 'short',
});
const timeFormatter = new Intl.DateTimeFormat('es-MX', { timeStyle: 'short' });

const toDate = (value?: string | number | Date | null): Date | null => {
    if (!value) return null;
    const d = value instanceof Date ? value : new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
};

export const formatDateMx = (value?: string | number | Date | null): string => {
    const d = toDate(value);
    return d ? dateFormatter.format(d) : '—';
};

export const formatDateTimeMx = (
    value?: string | number | Date | null,
): string => {
    const d = toDate(value);
    return d ? dateTimeFormatter.format(d) : '—';
};

export const formatTimeMx = (value?: string | number | Date | null): string => {
    const d = toDate(value);
    return d ? timeFormatter.format(d) : '—';
};
