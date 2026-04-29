const dateFormatter = new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' });

const dateTimeFormatter = new Intl.DateTimeFormat('es-MX', {
    dateStyle: 'long',
    timeStyle: 'short',
});

const timeFormatter = new Intl.DateTimeFormat('es-MX', { timeStyle: 'short' });

const isDateOnlyString = (value: string) => /^\d{4}-\d{2}-\d{2}$/.test(value);

const parseDateOnly = (value: string): Date | null => {
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (!match) return null;

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    const date = new Date(year, month - 1, day);

    return Number.isNaN(date.getTime()) ? null : date;
};

const toDate = (value?: string | number | Date | null): Date | null => {
    if (!value) return null;

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();

        if (!trimmed) return null;

        if (isDateOnlyString(trimmed)) {
            return parseDateOnly(trimmed);
        }

        const date = new Date(trimmed);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

export const formatDateMx = (value?: string | number | Date | null): string => {
    const date = toDate(value);

    return date ? dateFormatter.format(date) : '—';
};

export const formatDateTimeMx = (
    value?: string | number | Date | null,
): string => {
    const date = toDate(value);

    return date ? dateTimeFormatter.format(date) : '—';
};

export const formatTimeMx = (value?: string | number | Date | null): string => {
    const date = toDate(value);

    return date ? timeFormatter.format(date) : '—';
};

export const toDateInputValue = (
    value?: string | number | Date | null,
): string => {
    const date = toDate(value);

    if (!date) return '';

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};