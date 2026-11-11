export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
    ) {
        super(message);
    }
}
export async function api<T>(url: string, method = 'GET', body?: unknown): Promise<T> {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('AMERTANALA-XSRF='))
        ?.split('=')
        .slice(1)
        .join('=');
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
            ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new ApiError(
            response.status,
            String(first || data.message || (response.status === 419 ? 'Sesi berakhir. Muat ulang halaman.' : 'Permintaan gagal. Coba lagi.')),
        );
    }
    return response.json() as Promise<T>;
}
