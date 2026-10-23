const numberFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

/** 12500 -> "Rp 12.500" */
export function rupiah(value) {
    const n = Number(value) || 0;
    return (n < 0 ? '-Rp ' : 'Rp ') + numberFormat.format(Math.abs(n));
}

/** Angka ringkas untuk sumbu grafik: 1.500.000 -> "1,5 jt". */
export function compactRupiah(value) {
    const n = Math.abs(Number(value) || 0);
    if (n >= 1_000_000_000) return trim(n / 1_000_000_000) + ' M';
    if (n >= 1_000_000) return trim(n / 1_000_000) + ' jt';
    if (n >= 1_000) return trim(n / 1_000) + ' rb';
    return String(n);
}

function trim(n) {
    return n.toLocaleString('id-ID', { maximumFractionDigits: 1 });
}

/** Mengambil angka dari input teks, mis. "50.000" -> 50000. */
export function parseNumber(value) {
    const digits = String(value ?? '').replace(/[^\d]/g, '');
    return digits ? parseInt(digits, 10) : 0;
}

/** UUID v4; crypto.randomUUID hanya tersedia di HTTPS/localhost, jadi ada cadangan. */
export function uuid() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    const bytes = window.crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}
