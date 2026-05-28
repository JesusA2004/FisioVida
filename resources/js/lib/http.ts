/**
 * HTTP helpers for raw fetch calls.
 * Reads CSRF token from meta tag (set in app.blade.php).
 * Always include credentials so session cookie is sent.
 */

export function csrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
}

export function jsonHeaders(extra: Record<string, string> = {}): HeadersInit {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        ...extra,
    };
}

export async function postJson(url: string, body: unknown): Promise<Response> {
    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify(body),
    });
}

export async function patchJson(url: string, body: unknown = {}): Promise<Response> {
    return fetch(url, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify(body),
    });
}

export async function getJson(url: string): Promise<Response> {
    return fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
}
