<script setup lang="ts">
import {
    AmbientLight,
    ACESFilmicToneMapping,
    DirectionalLight,
    DoubleSide,
    EdgesGeometry,
    Group,
    LineBasicMaterial,
    LineSegments,
    Mesh,
    MeshPhysicalMaterial,
    MeshStandardMaterial,
    PCFSoftShadowMap,
    PMREMGenerator,
    PerspectiveCamera,
    PlaneGeometry,
    Scene,
    SRGBColorSpace,
    TextureLoader,
    WebGLRenderer,
} from 'three';
import type { Texture, WebGLRenderTarget } from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { RoundedBoxGeometry } from 'three/examples/jsm/geometries/RoundedBoxGeometry.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    src: string;
    alt: string;
}>();

const container = ref<HTMLDivElement | null>(null);

let animationFrame = 0;
let camera: PerspectiveCamera | null = null;
let group: Group | null = null;
let imageTexture: Texture | null = null;
let photoGeometry: PlaneGeometry | null = null;
let photoMaterial: MeshStandardMaterial | null = null;
let photoMesh: Mesh | null = null;
let pmremGenerator: PMREMGenerator | null = null;
let renderer: WebGLRenderer | null = null;
let resizeObserver: ResizeObserver | null = null;
let scene: Scene | null = null;
let reduceMotionQuery: MediaQueryList | null = null;
let isDragging = false;
let environmentTarget: WebGLRenderTarget | null = null;
let visibilityObserver: IntersectionObserver | null = null;
let isVisible = false;
let disposed = false;
let textureVersion = 0;
let lastFrame = 0;
const ready = ref(false);
const fallbackFailed = ref(false);
const resources = new Set<{ dispose: () => void }>();
function own<T extends { dispose: () => void }>(resource: T): T {
    resources.add(resource);

    return resource;
}
let lastPointer = { x: 0, y: 0 };

const params = {
    photoScale: 1.9,
    envIntensity: 1,
    opacity: 1,
    playing: true,
    globalSpeed: 1,
    yAxis: { mode: 'Spin', speed: 0.5, amp: 0.6 },
    xAxis: { enabled: true, speed: 0.4, amp: 0.2 },
    zAxis: { enabled: true, speed: 0.3, amp: 0.1 },
};
let time = 0;

function loadEnvironment() {
    if (!scene || !renderer) {
        return;
    }

    if (!pmremGenerator) {
        return;
    }

    const environment = new RoomEnvironment();

    try {
        environmentTarget = pmremGenerator.fromScene(environment, 0.04);
        scene.environment = environmentTarget.texture;
    } finally {
        environment.dispose();
    }

    scene.environmentIntensity = params.envIntensity;
}

function resize() {
    if (!container.value || !camera || !renderer) {
        return;
    }

    const { width, height } = container.value.getBoundingClientRect();

    if (width <= 0 || height <= 0) {
        return;
    }

    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
    requestRender();
}

function textureSource(src: string) {
    const url = new URL(src, window.location.href);

    if (url.pathname.startsWith('/storage/')) {
        return `${url.pathname}${url.search}${url.hash}`;
    }

    return url.toString();
}

function updatePhotoScale() {
    if (!photoMesh || !imageTexture?.image) {
        return;
    }

    const image = imageTexture.image as HTMLImageElement;
    const aspect = image.width / image.height;
    const scale = params.photoScale;

    if (aspect > 1) {
        photoMesh.scale.set(scale, scale / aspect, 1);

        return;
    }

    photoMesh.scale.set(scale * aspect, scale, 1);
}

function onPointerDown(event: PointerEvent) {
    params.playing = false;
    isDragging = true;
    lastPointer = { x: event.clientX, y: event.clientY };
    container.value?.setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent) {
    if (!isDragging || !group) {
        return;
    }

    const dx = event.clientX - lastPointer.x;
    const dy = event.clientY - lastPointer.y;

    group.rotation.y += dx * 0.01;
    group.rotation.x += dy * 0.01;
    lastPointer = { x: event.clientX, y: event.clientY };
    requestRender();
}

function onPointerUp(event: PointerEvent) {
    isDragging = false;

    if (!isPointerInside(event)) {
        params.playing = true;
    }

    releasePointerCapture(event.pointerId);
    requestRender();
}

function onPointerLeave(event: PointerEvent) {
    isDragging = false;
    params.playing = true;
    releasePointerCapture(event.pointerId);
    requestRender();
}

function releasePointerCapture(pointerId: number) {
    if (!container.value?.hasPointerCapture(pointerId)) {
        return;
    }

    container.value.releasePointerCapture(pointerId);
}

function isPointerInside(event: PointerEvent) {
    const bounds = container.value?.getBoundingClientRect();

    if (!bounds) {
        return false;
    }

    return (
        event.clientX >= bounds.left &&
        event.clientX <= bounds.right &&
        event.clientY >= bounds.top &&
        event.clientY <= bounds.bottom
    );
}

function requestRender() {
    if (
        disposed ||
        !ready.value ||
        !renderer ||
        !isVisible ||
        document.hidden ||
        animationFrame
    ) {
        return;
    }

    animationFrame = window.requestAnimationFrame(animate);
}

function syncRendering() {
    window.cancelAnimationFrame(animationFrame);
    animationFrame = 0;
    lastFrame = 0;
    requestRender();
}

function animate(now: number) {
    animationFrame = 0;

    if (
        disposed ||
        !ready.value ||
        !isVisible ||
        document.hidden ||
        !renderer ||
        !scene ||
        !camera ||
        !group
    ) {
        return;
    }

    const delta = lastFrame ? Math.min((now - lastFrame) / 1000, 0.05) : 0;
    lastFrame = now;

    if (!reduceMotionQuery?.matches) {
        time += delta * params.globalSpeed;

        if (params.playing) {
            if (params.yAxis.mode === 'Spin') {
                group.rotation.y +=
                    delta * params.yAxis.speed * params.globalSpeed;
            } else {
                group.rotation.y =
                    Math.sin(time * params.yAxis.speed) * params.yAxis.amp;
            }

            if (params.xAxis.enabled) {
                group.rotation.x =
                    Math.cos(time * params.xAxis.speed) * params.xAxis.amp;
            }

            if (params.zAxis.enabled) {
                group.rotation.z =
                    Math.sin(time * params.zAxis.speed * 0.7) *
                    params.zAxis.amp;
            }
        }
    }

    renderer.render(scene, camera);

    if (!reduceMotionQuery?.matches && params.playing) {
        requestRender();
    }
}

function loadPhoto() {
    const version = ++textureVersion;
    ready.value = false;

    if (renderer) {
        renderer.domElement.style.visibility = 'hidden';
    }

    fallbackFailed.value = false;
    imageTexture?.dispose();
    imageTexture = null;

    if (!photoMesh || !photoMaterial || disposed) {
        return;
    }

    photoMesh.visible = false;
    photoMaterial.map = null;
    photoMaterial.needsUpdate = true;

    try {
        imageTexture = new TextureLoader().load(
            textureSource(props.src),
            (texture) => {
                if (
                    disposed ||
                    version !== textureVersion ||
                    !photoMaterial ||
                    !photoMesh
                ) {
                    texture.dispose();

                    return;
                }

                texture.colorSpace = SRGBColorSpace;
                imageTexture = texture;
                photoMaterial.map = texture;
                photoMaterial.needsUpdate = true;
                updatePhotoScale();
                photoMesh.visible = true;
                ready.value = true;

                if (renderer) {
                    renderer.domElement.style.visibility = 'visible';
                }

                requestRender();
            },
            undefined,
            () => {
                if (disposed || version !== textureVersion) {
                    return;
                }

                imageTexture?.dispose();
                imageTexture = null;
                ready.value = false;
            },
        );
    } catch {
        ready.value = false;
    }

    requestRender();
}

function initialize() {
    if (!container.value) {
        return;
    }

    reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    reduceMotionQuery.addEventListener('change', syncRendering);
    document.addEventListener('visibilitychange', syncRendering);
    scene = new Scene();
    scene.background = null;
    camera = new PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0, 5.8);

    renderer = new WebGLRenderer({
        alpha: true,
        antialias: true,
        powerPreference: 'high-performance',
    });
    renderer.outputColorSpace = SRGBColorSpace;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = PCFSoftShadowMap;
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.35;
    renderer.setClearColor(0x000000, 0);
    renderer.domElement.style.background = 'transparent';
    renderer.domElement.style.position = 'absolute';
    renderer.domElement.style.inset = '0';
    renderer.domElement.style.visibility = 'hidden';
    renderer.domElement.style.zIndex = '10';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.width = '100%';
    container.value.appendChild(renderer.domElement);

    container.value.addEventListener('pointerdown', onPointerDown);
    container.value.addEventListener('pointermove', onPointerMove);
    container.value.addEventListener('pointerup', onPointerUp);
    container.value.addEventListener('pointercancel', onPointerUp);
    container.value.addEventListener('pointerleave', onPointerLeave);

    pmremGenerator = new PMREMGenerator(renderer);
    loadEnvironment();

    scene.add(new AmbientLight(0xffffff, 0.35));

    const keyLight = new DirectionalLight(0xffffff, 5.2);
    keyLight.position.set(3, 5, 5);
    scene.add(keyLight);

    const rimLight = new DirectionalLight(0x1d5dff, 8.5);
    rimLight.position.set(-5, 2, -4);
    scene.add(rimLight);

    const warmEdgeLight = new DirectionalLight(0xff9d2e, 5.5);
    warmEdgeLight.position.set(4, -2, 3);
    scene.add(warmEdgeLight);

    group = new Group();
    group.position.y = -0.25;
    scene.add(group);

    photoGeometry = own(new PlaneGeometry(1, 1));
    photoMaterial = own(
        new MeshStandardMaterial({
            alphaTest: 0.5,
            color: 0xffffff,
            depthWrite: true,
            metalness: 0.1,
            roughness: 0.2,
            side: DoubleSide,
            transparent: false,
        }),
    );
    photoMesh = new Mesh(photoGeometry, photoMaterial);
    photoMesh.position.set(0, 0, 0);
    photoMesh.renderOrder = 0;
    photoMesh.visible = false;
    group.add(photoMesh);

    loadPhoto();

    const glassGeometry = own(new RoundedBoxGeometry(2.15, 3.1, 0.92, 48, 0.3));
    const glass = new Mesh(
        glassGeometry,
        own(
            new MeshPhysicalMaterial({
                attenuationColor: 0x1f5cff,
                attenuationDistance: 2.4,
                clearcoat: 1,
                clearcoatRoughness: 0.0,
                color: 0x0b0f1c,
                depthWrite: false,
                envMapIntensity: 5.5,
                ior: 1.8,
                metalness: 0,
                opacity: params.opacity * 0.18,
                roughness: 0,
                side: DoubleSide,
                specularIntensity: 1.8,
                thickness: 2.4,
                transmission: 1.0,
                transparent: true,
            }),
        ),
    );
    glass.renderOrder = 1;
    group.add(glass);

    const edgeLines = new LineSegments(
        own(new EdgesGeometry(glassGeometry, 18)),
        own(
            new LineBasicMaterial({
                color: 0x4f7cff,
                transparent: true,
                opacity: 0.9,
            }),
        ),
    );
    edgeLines.renderOrder = 2;
    group.add(edgeLines);

    const whiteEdgeLines = new LineSegments(
        own(new EdgesGeometry(glassGeometry, 32)),
        own(
            new LineBasicMaterial({
                color: 0xffffff,
                transparent: true,
                opacity: 0.35,
            }),
        ),
    );
    whiteEdgeLines.renderOrder = 3;
    group.add(whiteEdgeLines);

    resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(container.value);
    resize();
    visibilityObserver = new IntersectionObserver(([entry]) => {
        isVisible = entry.isIntersecting;
        syncRendering();
    });
    visibilityObserver.observe(container.value);
    renderer.domElement.addEventListener('webglcontextlost', onContextLost);
}

function onContextLost(event: Event) {
    event.preventDefault();
    cleanup();
}

function cleanup() {
    disposed = true;
    ready.value = false;
    ++textureVersion;
    window.cancelAnimationFrame(animationFrame);
    resizeObserver?.disconnect();
    container.value?.removeEventListener('pointerdown', onPointerDown);
    container.value?.removeEventListener('pointermove', onPointerMove);
    container.value?.removeEventListener('pointerup', onPointerUp);
    container.value?.removeEventListener('pointercancel', onPointerUp);
    container.value?.removeEventListener('pointerleave', onPointerLeave);
    imageTexture?.dispose();
    visibilityObserver?.disconnect();
    reduceMotionQuery?.removeEventListener('change', syncRendering);
    document.removeEventListener('visibilitychange', syncRendering);
    resources.forEach((resource) => resource.dispose());
    resources.clear();
    environmentTarget?.dispose();
    environmentTarget = null;
    pmremGenerator?.dispose();
    pmremGenerator = null;
    renderer?.domElement.removeEventListener('webglcontextlost', onContextLost);
    renderer?.dispose();
    renderer?.forceContextLoss();
    renderer?.domElement.remove();
    renderer = null;
    camera = null;
    photoGeometry = null;
    reduceMotionQuery = null;
    visibilityObserver = null;
    resizeObserver = null;
    imageTexture = null;
    photoMaterial = null;
    photoMesh = null;
    scene?.clear();
    scene = null;
    group = null;
}

watch(() => props.src, loadPhoto);
onMounted(() => {
    try {
        initialize();
    } catch {
        cleanup();
    }
});
onBeforeUnmount(cleanup);
</script>

<template>
    <div
        ref="container"
        data-native-gesture
        class="relative h-full w-full cursor-grab touch-none bg-transparent active:cursor-grabbing"
        style="perspective: 900px"
        role="img"
        :aria-label="alt"
    >
        <img
            v-if="!ready && !fallbackFailed"
            :src="src"
            alt=""
            class="h-full w-full object-contain"
            @error="fallbackFailed = true"
        />
        <span
            v-else-if="!ready"
            class="grid h-full place-items-center text-public-subtle"
            >{{ alt }}</span
        >
    </div>
</template>
