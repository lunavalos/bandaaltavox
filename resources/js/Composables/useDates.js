// Las fechas "solo día" (ej. fecha del evento) llegan como "2026-10-15" o
// "2026-10-15T00:00:00.000000Z". `new Date()` las interpreta como medianoche UTC,
// y en México (UTC-6) se muestran como el día anterior. Aquí se interpretan en hora local.
const DATE_ONLY = /^(\d{4})-(\d{2})-(\d{2})(?:T00:00:00(?:\.0+)?Z?)?$/;

export function parseDate(value) {
    if (!value) return null;
    if (value instanceof Date) return value;
    const m = DATE_ONLY.exec(value);
    if (m) return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
    return new Date(value);
}

// Fecha de hoy en hora local, formato YYYY-MM-DD (para inputs type="date")
export function todayISO() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

// Convierte cualquier valor de fecha a YYYY-MM-DD para inputs type="date"
export function toDateInput(value) {
    if (!value) return '';
    return String(value).split('T')[0];
}
