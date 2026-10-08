<script setup>
import { AppButton } from '@/components/ui'
import { X } from '@lucide/vue'
defineProps({ error: String, message: String, busy: Boolean })
defineEmits(['clear-error', 'clear-message'])
</script>
<template>
    <div v-if="error" role="alert" class="alert error">
        {{ error
        }}<AppButton variant="ghost" @click="$emit('clear-error')" aria-label="Fechar erro"
            ><X :size="16"
        /></AppButton>
    </div>
    <div v-if="message" role="status" class="alert success">
        {{ message
        }}<AppButton variant="ghost" @click="$emit('clear-message')" aria-label="Fechar mensagem">
            <X :size="16" />
        </AppButton>
    </div>
    <div v-if="busy" role="status" class="loading-bar">Processando…</div>
</template>
