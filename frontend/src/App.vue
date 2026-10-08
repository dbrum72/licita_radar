<script setup>
import { Radar } from '@lucide/vue'
import { provideWorkspace } from '@/composables/useWorkspace'
import LoginPage from '@/views/auth/LoginPage.vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
const { user, booting } = provideWorkspace()
</script>
<template>
    <div v-if="booting" class="boot">
        <Radar :size="38" />
        <p>Carregando LicitaRadar…</p>
    </div>
    <LoginPage v-else-if="!user" />
    <DefaultLayout v-else />
</template>
