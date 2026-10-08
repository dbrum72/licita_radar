<script setup>
import { AppConfirmDialog } from '@/components/ui'
import { useWorkspace } from '@/composables/useWorkspace'
const { pendingDelete, busy, error, cancelDelete, confirmDelete } = useWorkspace()
</script>
<template>
    <AppConfirmDialog
        :open="Boolean(pendingDelete)"
        :loading="busy"
        title="Excluir anexo"
        :message="`Excluir o anexo “${pendingDelete?.name}” e os requisitos extraídos dele?`"
        :error="error"
        confirm-label="Excluir anexo"
        @cancel="cancelDelete"
        @confirm="confirmDelete"
        ><p>Esta ação preserva a oportunidade e os documentos da empresa.</p></AppConfirmDialog
    >
</template>
