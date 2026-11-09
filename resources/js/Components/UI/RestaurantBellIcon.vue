<template>
    <!-- Icono de campanita de servicio de restaurante (Service Desk Bell) -->
    <svg
        :class="props.class || 'w-6 h-6'"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        xmlns="http://www.w3.org/2000/svg"
    >
        <!-- Pulsador superior de la campana -->
        <path d="M12 2v2" />
        <circle cx="12" cy="2" r="1" fill="currentColor" />
        <!-- Cúpula de la campana de servicio de restaurante -->
        <path d="M4 16c0-4.418 3.582-8 8-8s8 3.582 8 8H4z" fill="currentColor" fill-opacity="0.15" />
        <!-- Base de la campana -->
        <rect x="2" y="16" width="20" height="3" rx="1.5" fill="currentColor" />
        <line x1="1" y1="21" x2="23" y2="21" />
    </svg>
</template>

<script setup>
const props = defineProps({
    class: {
        type: String,
        default: 'w-6 h-6',
    },
});
</script>

