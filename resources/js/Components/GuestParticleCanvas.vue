<script setup>
import { nextTick, onMounted, onUnmounted, ref } from 'vue';

const wrapRef = ref(null);
const canvasRef = ref(null);

let ctx = null;
let dpr = 1;
let w = 0;
let h = 0;

/** Cursor suavizado */
let mx = 0;
let my = 0;
let targetX = 0;
let targetY = 0;

/** 0 = puntero fuera / sin interacción; 1 = sobre la vista */
let haloStrength = 0;
let targetHalo = 0;

let rafId = 0;
let time = 0;

const GOLDEN = Math.PI * (3 - Math.sqrt(5));

function resize() {
    const el = wrapRef.value;
    const canvas = canvasRef.value;
    if (!el || !canvas) return;
    const r = el.getBoundingClientRect();
    w = Math.max(1, Math.floor(r.width));
    h = Math.max(1, Math.floor(r.height));
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.floor(w * dpr);
    canvas.height = Math.floor(h * dpr);
    canvas.style.width = `${w}px`;
    canvas.style.height = `${h}px`;
    ctx = canvas.getContext('2d', { alpha: true });
    if (ctx) ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    mx = w * 0.5;
    my = h * 0.5;
    targetX = mx;
    targetY = my;
}

function onPointerMove(e) {
    const el = wrapRef.value;
    if (!el || w < 1 || h < 1) return;
    const r = el.getBoundingClientRect();
    const pad = 40;
    const inside =
        e.clientX >= r.left - pad &&
        e.clientX <= r.right + pad &&
        e.clientY >= r.top - pad &&
        e.clientY <= r.bottom + pad;
    if (inside) {
        targetX = e.clientX - r.left;
        targetY = e.clientY - r.top;
        targetHalo = 1;
    } else {
        targetHalo = 0;
    }
}

function drawPill(px, py, mx0, my0, alphaMul, hueBase) {
    const dx = px - mx0;
    const dy = py - my0;
    const dist = Math.hypot(dx, dy) || 0.0001;
    const angle = Math.atan2(dy, dx) + Math.PI / 2;

    const edge = 1 - Math.min(1, (dist - 22) / 160);
    const alpha = (0.28 + 0.55 * edge) * alphaMul;
    const scale = 0.35 + 0.65 * edge;

    const pillW = Math.max(0.85, 2.2 * scale);
    const pillH = Math.max(2.2, 6.8 * scale);

    const hue = hueBase + Math.sin((px + py) * 0.04) * 10;

    ctx.save();
    ctx.translate(px, py);
    ctx.rotate(angle);
    ctx.fillStyle = `hsla(${hue}, 62%, 58%, ${alpha})`;
    ctx.beginPath();
    if (typeof ctx.roundRect === 'function') {
        ctx.roundRect(-pillW * 0.5, -pillH * 0.5, pillW, pillH, pillW * 0.45);
    } else {
        ctx.rect(-pillW * 0.5, -pillH * 0.5, pillW, pillH);
    }
    ctx.fill();
    ctx.restore();
}

/**
 * Solo partículas en anillos animados alrededor del cursor (nada de rejilla global).
 */
function drawHaloAroundCursor(reduceMotion) {
    const rMax = Math.min(200, Math.min(w, h) * 0.26);
    const rMin = 26;

    if (!reduceMotion) {
        time += 0.014;
    }

    // Capa exterior: espiral dorada + rotación
    const nOuter = 72;
    for (let i = 0; i < nOuter; i++) {
        const t = i / nOuter;
        const spin = time * (reduceMotion ? 0 : 0.55);
        const angle = i * GOLDEN + spin + Math.sin(i * 0.31 + time) * 0.12;
        const wave = 0.5 + 0.5 * Math.sin(time * 0.9 + i * 0.17);
        const r = rMin + (rMax - rMin) * (0.25 + 0.75 * Math.sqrt(t)) * (0.75 + 0.25 * wave);
        const px = mx + Math.cos(angle) * r;
        const py = my + Math.sin(angle) * r;
        if (px < -20 || py < -20 || px > w + 20 || py > h + 20) continue;
        drawPill(px, py, mx, my, haloStrength, 215 + t * 40);
    }

    // Capa interior: menos puntos, giro contrario
    const nInner = 44;
    for (let i = 0; i < nInner; i++) {
        const t = i / nInner;
        const spin = time * (reduceMotion ? 0 : -0.65);
        const angle = i * GOLDEN * 1.3 + spin + t * Math.PI * 2;
        const r = rMin + (rMax * 0.72 - rMin) * (0.35 + 0.65 * t) + Math.sin(time * 1.1 + i * 0.4) * 5;
        const px = mx + Math.cos(angle) * r;
        const py = my + Math.sin(angle) * r;
        if (px < -20 || py < -20 || px > w + 20 || py > h + 20) continue;
        drawPill(px, py, mx, my, haloStrength * 0.92, 245 + t * 35);
    }
}

function drawFrame() {
    if (!ctx || w < 8 || h < 8) return;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const lerp = reduceMotion ? 1 : 0.11;
    mx += (targetX - mx) * lerp;
    my += (targetY - my) * lerp;
    haloStrength += (targetHalo - haloStrength) * (reduceMotion ? 0.2 : 0.08);

    ctx.clearRect(0, 0, w, h);

    if (haloStrength < 0.02) return;

    drawHaloAroundCursor(reduceMotion);
}

function loop() {
    drawFrame();
    rafId = requestAnimationFrame(loop);
}

let ro = null;

onMounted(async () => {
    await nextTick();
    requestAnimationFrame(() => resize());

    window.addEventListener('resize', resize, { passive: true });
    window.addEventListener('pointermove', onPointerMove, { passive: true });

    if (wrapRef.value && typeof ResizeObserver !== 'undefined') {
        ro = new ResizeObserver(() => resize());
        ro.observe(wrapRef.value);
    }

    rafId = requestAnimationFrame(loop);
});

onUnmounted(() => {
    window.removeEventListener('resize', resize);
    window.removeEventListener('pointermove', onPointerMove);
    const el = wrapRef.value;
    if (ro && el) ro.unobserve(el);
    ro = null;
    cancelAnimationFrame(rafId);
});
</script>

<template>
    <div
        ref="wrapRef"
        class="pointer-events-none absolute inset-0 z-[1] overflow-hidden"
        aria-hidden="true"
    >
        <canvas ref="canvasRef" class="block h-full w-full" />
    </div>
</template>
