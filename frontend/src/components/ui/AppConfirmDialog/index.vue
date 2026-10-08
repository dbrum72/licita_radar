<script setup>
import AppDialog from '../AppDialog/index.vue'
import AppButton from '../AppButton/index.vue'
import { appConfirmDialogProps } from './props'
const props = defineProps(appConfirmDialogProps)
const emit = defineEmits(['confirm', 'cancel'])
const cancel = () => {
    if (!props.loading) emit('cancel')
}
const confirm = () => {
    if (!props.loading) emit('confirm')
}
</script>
<template>
    <AppDialog :open="open" :busy="loading" :title="title" @close="cancel">
        <p>{{ message }}</p>
        <slot />
        <p v-if="error" role="alert" class="error">{{ error }}</p>
        <div class="profile-actions">
            <AppButton variant="outline" :disabled="loading" @click="cancel">{{
                cancelLabel
            }}</AppButton>
            <AppButton variant="danger" :disabled="loading" @click="confirm">{{
                loading ? 'Excluindo…' : confirmLabel
            }}</AppButton>
        </div>
    </AppDialog>
</template>
