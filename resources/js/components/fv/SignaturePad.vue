<script setup lang="ts">
import { ref, onMounted } from 'vue';

const props = withDefaults(defineProps<{
    modelValue?: string;
    disabled?: boolean;
    height?: number;
}>(), {
    modelValue: '',
    disabled: false,
    height: 160,
});

const emit = defineEmits<{
    (e: 'update:modelValue', val: string): void;
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
const drawing = ref(false);
const hasDrawing = ref(false);

const getCtx = (): CanvasRenderingContext2D | null => {
    return canvas.value?.getContext('2d') ?? null;
};

const initCanvas = () => {
    const c = canvas.value;
    const ctx = getCtx();
    if (!c || !ctx) return;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, c.width, c.height);
    ctx.strokeStyle = '#1e1b4b';
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
};

const getPos = (e: MouseEvent | Touch, el: HTMLCanvasElement) => {
    const rect = el.getBoundingClientRect();
    const sx = el.width / rect.width;
    const sy = el.height / rect.height;
    const cx = 'clientX' in e ? e.clientX : (e as Touch).clientX;
    const cy = 'clientY' in e ? e.clientY : (e as Touch).clientY;
    return { x: (cx - rect.left) * sx, y: (cy - rect.top) * sy };
};

const onMouseDown = (e: MouseEvent) => {
    if (props.disabled) return;
    drawing.value = true;
    const ctx = getCtx();
    if (!ctx || !canvas.value) return;
    const { x, y } = getPos(e, canvas.value);
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const onMouseMove = (e: MouseEvent) => {
    if (!drawing.value || props.disabled || !canvas.value) return;
    const ctx = getCtx();
    if (!ctx) return;
    const { x, y } = getPos(e, canvas.value);
    ctx.lineTo(x, y);
    ctx.stroke();
    hasDrawing.value = true;
};

const onMouseUp = () => {
    if (!drawing.value) return;
    drawing.value = false;
    emitValue();
};

const onTouchStart = (e: TouchEvent) => {
    if (props.disabled) return;
    e.preventDefault();
    drawing.value = true;
    const ctx = getCtx();
    if (!ctx || !canvas.value) return;
    const { x, y } = getPos(e.touches[0], canvas.value);
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const onTouchMove = (e: TouchEvent) => {
    if (!drawing.value || props.disabled || !canvas.value) return;
    e.preventDefault();
    const ctx = getCtx();
    if (!ctx) return;
    const { x, y } = getPos(e.touches[0], canvas.value);
    ctx.lineTo(x, y);
    ctx.stroke();
    hasDrawing.value = true;
};

const onTouchEnd = (e: TouchEvent) => {
    e.preventDefault();
    onMouseUp();
};

const emitValue = () => {
    if (hasDrawing.value && canvas.value) {
        emit('update:modelValue', canvas.value.toDataURL('image/png'));
    }
};

const clear = () => {
    hasDrawing.value = false;
    initCanvas();
    emit('update:modelValue', '');
};

const isEmpty = () => !hasDrawing.value;

defineExpose({ clear, isEmpty });

onMounted(() => {
    initCanvas();
});
</script>

<template>
    <div class="w-full select-none">
        <canvas
            ref="canvas"
            :width="800"
            :height="height"
            class="w-full rounded-xl border-2 border-dashed bg-white touch-none"
            :class="[
                disabled
                    ? 'border-border opacity-60 cursor-not-allowed'
                    : 'border-border hover:border-primary cursor-crosshair',
                hasDrawing ? 'border-emerald-400' : ''
            ]"
            @mousedown="onMouseDown"
            @mousemove="onMouseMove"
            @mouseup="onMouseUp"
            @mouseleave="onMouseUp"
            @touchstart="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
        />
        <div class="mt-2 flex items-center justify-between">
            <p class="text-xs text-muted-foreground">
                <span v-if="hasDrawing" class="text-emerald-600 dark:text-emerald-400 font-medium">✓ Firma capturada</span>
                <span v-else>Dibuja tu firma con el mouse o dedo</span>
            </p>
            <button
                type="button"
                class="text-xs text-rose-500 hover:text-rose-600 dark:text-rose-400 font-medium transition-colors"
                :disabled="disabled"
                @click="clear"
            >
                Limpiar
            </button>
        </div>
    </div>
</template>
