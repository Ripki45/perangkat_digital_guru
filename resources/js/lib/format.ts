const dateFormatter = (options: Intl.DateTimeFormatOptions) =>
    new Intl.DateTimeFormat('id-ID', options);

export function formatDate(
    value?: string | null,
    options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    },
): string {
    if (!value) {
        return '—';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-').map(Number);

        return dateFormatter(options).format(new Date(year, month - 1, day));
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return dateFormatter(options).format(date);
}

export function formatDateTime(value?: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export function formatBytes(bytes: number): string {
    if (!bytes) {
        return '0 KB';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const value = bytes / Math.pow(1024, index);

    return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

export function greeting(date = new Date()): string {
    const hour = date.getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    if (hour < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
}

export function classLabel(kelas: string): string {
    if (!kelas) {
        return 'Kelas —';
    }

    return /^kelas\b/i.test(kelas) ? kelas : `Kelas ${kelas}`;
}

export function todayLabel(date = new Date()): string {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export function romanClass(kelas: string): string {
    const map: Record<string, string> = {
        '1': 'I',
        '2': 'II',
        '3': 'III',
        '4': 'IV',
        '5': 'V',
        '6': 'VI',
    };

    return map[kelas] ?? kelas;
}
