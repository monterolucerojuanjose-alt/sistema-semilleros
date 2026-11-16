/**
 * Escala de calificación del sistema (0 a 100), en un único lugar.
 *
 * Antes cada pantalla clasificaba las notas a su manera: «Mis notas» decía
 * «Aprobado» para un 75 y el detalle de la misma nota decía «Bueno»; unas
 * usaban verde desde 80 y otras desde 90. Todas las pantallas que muestran
 * una nota deben usar estas funciones para que se lean igual en todo el
 * sistema.
 */

/** Nota mínima aprobatoria. Coincide con MisNotasController::NOTA_APROBATORIA. */
export const NOTA_APROBATORIA = 60;

const NIVELES = [
    {
        minimo: 90,
        texto: 'Excelente',
        texto_color: 'text-emerald-700',
        insignia: 'bg-emerald-100 text-emerald-800',
        barra: 'bg-emerald-500',
        fondo: 'bg-emerald-50',
        trazo: '#10b981',
    },
    {
        minimo: 80,
        texto: 'Sobresaliente',
        texto_color: 'text-blue-700',
        insignia: 'bg-blue-100 text-blue-800',
        barra: 'bg-blue-500',
        fondo: 'bg-blue-50',
        trazo: '#3b82f6',
    },
    {
        minimo: 70,
        texto: 'Bueno',
        texto_color: 'text-indigo-700',
        insignia: 'bg-indigo-100 text-indigo-800',
        barra: 'bg-indigo-500',
        fondo: 'bg-indigo-50',
        trazo: '#6366f1',
    },
    {
        minimo: NOTA_APROBATORIA,
        texto: 'Aceptable',
        texto_color: 'text-amber-700',
        insignia: 'bg-amber-100 text-amber-800',
        barra: 'bg-amber-500',
        fondo: 'bg-amber-50',
        trazo: '#f59e0b',
    },
    {
        minimo: -Infinity,
        texto: 'Insuficiente',
        texto_color: 'text-red-700',
        insignia: 'bg-red-100 text-red-800',
        barra: 'bg-red-500',
        fondo: 'bg-red-50',
        trazo: '#ef4444',
    },
];

const SIN_NOTA = {
    texto: 'Sin calificar',
    texto_color: 'text-gray-400',
    insignia: 'bg-gray-100 text-gray-600',
    barra: 'bg-gray-300',
    fondo: 'bg-gray-50',
    trazo: '#d1d5db',
};

const esNumero = (valor) => valor !== null && valor !== undefined && valor !== '' && !Number.isNaN(Number(valor));

/** Nivel de desempeño (texto y clases de color) de una nota de 0 a 100. */
export function nivelNota(nota) {
    if (!esNumero(nota)) return SIN_NOTA;
    const n = Number(nota);
    return NIVELES.find((nivel) => n >= nivel.minimo);
}

/** ¿La nota alcanza el mínimo aprobatorio? */
export const aprobada = (nota) => esNumero(nota) && Number(nota) >= NOTA_APROBATORIA;

/**
 * Nota con formato colombiano (coma decimal): 87,5 · 100 · —
 * Sin decimales cuando la nota es entera, para no escribir «100,0».
 */
export function formatoNota(nota, decimales = 1) {
    if (!esNumero(nota)) return '—';
    const n = Number(nota);
    return n.toLocaleString('es-CO', {
        minimumFractionDigits: Number.isInteger(n) ? 0 : decimales,
        maximumFractionDigits: decimales,
    });
}

/** Porcentaje (0–100) para dibujar barras y anillos. */
export const porcentajeNota = (nota) => (esNumero(nota) ? Math.max(0, Math.min(100, Number(nota))) : 0);

/** Lista de niveles, para mostrar la leyenda de la escala. */
export const ESCALA = NIVELES.map((nivel, i) => ({
    texto: nivel.texto,
    insignia: nivel.insignia,
    rango: i === 0
        ? `${nivel.minimo}–100`
        : nivel.minimo === -Infinity
            ? `0–${NIVELES[i - 1].minimo - 0.1}`.replace('.', ',')
            : `${nivel.minimo}–${NIVELES[i - 1].minimo - 0.1}`.replace('.', ','),
}));
