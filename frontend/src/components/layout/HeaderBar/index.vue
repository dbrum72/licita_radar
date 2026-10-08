<script setup>
defineProps({ user: { type: Object, required: true }, title: String })
</script>
<template>
    <header class="topbar">
        <span
            >Espaço de trabalho <span class="separator">/</span> <strong>{{ title }}</strong></span
        >
        <div>
            <span class="source-pill"><span class="dot"></span>PNCP conectado por API</span
            ><span class="avatar small">{{ user.name?.charAt(0) }}</span>
        </div>
    </header>
</template>
