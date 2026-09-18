<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

type Particle = {
    x: number;
    y: number;
    radius: number;
    vx: number;
    vy: number;
};

const canvasRef = ref<HTMLCanvasElement | null>(null);
const reducedMotion = ref(false);

let frame = 0;
let mediaReduce: MediaQueryList | null = null;
let particles: Particle[] = [];
let pointer = { x: -9999, y: -9999 };
const maxParticles = 120;

function randomBetween(min: number, max: number) {
    return min + Math.random() * (max - min);
}

function makeParticle(x?: number, y?: number): Particle {
    return {
        x: x ?? randomBetween(0, window.innerWidth),
        y: y ?? randomBetween(0, window.innerHeight),
        radius: randomBetween(1, 3),
        vx: randomBetween(-1.5, 1.5),
        vy: randomBetween(-1.5, 1.5),
    };
}

function seedParticles() {
    const count = Math.min(
        maxParticles,
        Math.max(
            70,
            Math.round((window.innerWidth * window.innerHeight) / 14000),
        ),
    );

    particles = Array.from({ length: count }, () => makeParticle());
}

function resizeCanvas(
    canvas: HTMLCanvasElement,
    context: CanvasRenderingContext2D,
) {
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    const width = Math.round(window.innerWidth * ratio);
    const height = Math.round(window.innerHeight * ratio);

    if (canvas.width === width && canvas.height === height) {
        return;
    }

    canvas.width = width;
    canvas.height = height;
    canvas.style.width = `${window.innerWidth}px`;
    canvas.style.height = `${window.innerHeight}px`;
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    seedParticles();
}

function wrapParticle(particle: Particle) {
    if (particle.x < -20) {
        particle.x = window.innerWidth + 20;
    } else if (particle.x > window.innerWidth + 20) {
        particle.x = -20;
    }

    if (particle.y < -20) {
        particle.y = window.innerHeight + 20;
    } else if (particle.y > window.innerHeight + 20) {
        particle.y = -20;
    }
}

function drawLinkedLine(
    context: CanvasRenderingContext2D,
    fromX: number,
    fromY: number,
    toX: number,
    toY: number,
    opacity: number,
) {
    const lineColor = document.documentElement.classList.contains('dark')
        ? `rgba(255, 255, 255, ${opacity})`
        : `rgba(15, 23, 42, ${opacity * 0.42})`;

    context.beginPath();
    context.moveTo(fromX, fromY);
    context.lineTo(toX, toY);
    context.strokeStyle = lineColor;
    context.lineWidth = 1;
    context.stroke();
}

function draw() {
    frame = 0;

    const canvas = canvasRef.value;
    const context = canvas?.getContext('2d');

    if (!canvas || !context || document.visibilityState === 'hidden') {
        return;
    }

    resizeCanvas(canvas, context);
    context.clearRect(0, 0, window.innerWidth, window.innerHeight);

    particles.forEach((particle, index) => {
        if (!reducedMotion.value) {
            particle.x += particle.vx;
            particle.y += particle.vy;
            wrapParticle(particle);
        }

        context.beginPath();
        context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
        context.fillStyle = document.documentElement.classList.contains('dark')
            ? 'rgba(255, 255, 255, 0.96)'
            : 'rgba(15, 23, 42, 0.52)';
        context.fill();

        for (let next = index + 1; next < particles.length; next += 1) {
            const other = particles[next];
            const dx = particle.x - other.x;
            const dy = particle.y - other.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < 250) {
                drawLinkedLine(
                    context,
                    particle.x,
                    particle.y,
                    other.x,
                    other.y,
                    0.4 * (1 - distance / 250),
                );
            }
        }

        const pointerDx = particle.x - pointer.x;
        const pointerDy = particle.y - pointer.y;
        const pointerDistance = Math.sqrt(
            pointerDx * pointerDx + pointerDy * pointerDy,
        );

        if (pointerDistance < 140) {
            drawLinkedLine(
                context,
                particle.x,
                particle.y,
                pointer.x,
                pointer.y,
                1 - pointerDistance / 140,
            );
        }
    });

    if (!reducedMotion.value) {
        frame = window.requestAnimationFrame(draw);
    }
}

function ensureDraw() {
    if (!frame) {
        frame = window.requestAnimationFrame(draw);
    }
}

function onPointerMove(event: PointerEvent) {
    pointer = {
        x: event.clientX,
        y: event.clientY,
    };
}

function onClick(event: MouseEvent) {
    particles.splice(0, Math.max(0, particles.length + 4 - maxParticles));

    for (let index = 0; index < 4; index += 1) {
        particles.push(makeParticle(event.clientX, event.clientY));
    }

    ensureDraw();
}

function onResize() {
    seedParticles();
    ensureDraw();
}

function syncReducedMotion() {
    reducedMotion.value = Boolean(mediaReduce?.matches);
    ensureDraw();
}

function onVisibilityChange() {
    if (document.visibilityState === 'hidden' && frame) {
        window.cancelAnimationFrame(frame);
        frame = 0;

        return;
    }

    ensureDraw();
}

onMounted(() => {
    mediaReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    syncReducedMotion();
    mediaReduce.addEventListener('change', syncReducedMotion);
    seedParticles();
    window.addEventListener('resize', onResize, { passive: true });
    window.addEventListener('pointermove', onPointerMove, { passive: true });
    window.addEventListener('click', onClick);
    document.addEventListener('visibilitychange', onVisibilityChange);
    ensureDraw();
});

onBeforeUnmount(() => {
    if (frame) {
        window.cancelAnimationFrame(frame);
    }

    mediaReduce?.removeEventListener('change', syncReducedMotion);
    window.removeEventListener('resize', onResize);
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('click', onClick);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});
</script>

<template>
    <div
        class="pointer-events-none fixed inset-0 z-0 overflow-hidden bg-public-bg"
        aria-hidden="true"
    >
        <div class="stars absolute inset-0" />
        <div class="twinkle absolute inset-0" />
        <canvas ref="canvasRef" class="absolute inset-0" />
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_50%_40%,transparent_0%,rgba(248,250,252,0.10)_45%,rgba(248,250,252,0.76)_100%)] dark:bg-[radial-gradient(circle_at_50%_40%,transparent_0%,rgba(0,0,0,0.12)_45%,rgba(0,0,0,0.78)_100%)]"
        />
    </div>
</template>

<style scoped>
.stars {
    background-image:
        radial-gradient(circle, rgba(15, 23, 42, 0.36) 1px, transparent 1.5px),
        radial-gradient(circle, rgba(15, 23, 42, 0.28) 1px, transparent 1.4px),
        radial-gradient(circle, rgba(15, 23, 42, 0.2) 1px, transparent 1.3px);
    background-position:
        0 0,
        48px 86px,
        132px 38px;
    background-size:
        220px 220px,
        310px 310px,
        420px 420px;
}

.twinkle {
    animation: move-twink-back 200s linear infinite;
    background-image:
        radial-gradient(circle, rgba(15, 23, 42, 0.32) 1px, transparent 2px),
        radial-gradient(circle, rgba(15, 23, 42, 0.22) 1px, transparent 2px);
    background-position:
        84px 40px,
        180px 140px;
    background-size:
        260px 260px,
        380px 380px;
    opacity: 0.82;
}

:global(.dark) .stars {
    background-image:
        radial-gradient(
            circle,
            rgba(255, 255, 255, 0.95) 1px,
            transparent 1.5px
        ),
        radial-gradient(
            circle,
            rgba(255, 255, 255, 0.7) 1px,
            transparent 1.4px
        ),
        radial-gradient(circle, rgba(255, 255, 255, 0.5) 1px, transparent 1.3px);
}

:global(.dark) .twinkle {
    background-image:
        radial-gradient(circle, rgba(255, 255, 255, 0.72) 1px, transparent 2px),
        radial-gradient(circle, rgba(255, 255, 255, 0.42) 1px, transparent 2px);
}

@keyframes move-twink-back {
    from {
        background-position:
            84px 40px,
            180px 140px;
    }

    to {
        background-position:
            -9916px 5040px,
            -9820px 5140px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .twinkle {
        animation: none;
    }
}
</style>
