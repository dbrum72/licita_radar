<script setup>
import { AppSelect, AppInput, AppTextarea } from '@/components/forms'
import { AppButton } from '@/components/ui'
import AppDialog from '@/components/ui/AppDialog/index.vue'
import { useWorkspace } from '@/composables/useWorkspace'

const { busy, error, documents, reviewForm, saveReview } = useWorkspace()
</script>

<template>
    <AppDialog
        :open="Boolean(reviewForm)"
        :busy="busy"
        title="Revisar requisito"
        @close="reviewForm = null"
        ><template v-if="reviewForm"
            ><p>{{ reviewForm.description }}</p>
            <form @submit.prevent="saveReview">
                <AppSelect
                    v-model="reviewForm.status"
                    label="Conclusão"
                    id="requirementreviewdialog-1"
                    :options="[
                        { value: 'pending', label: 'Pendente' },
                        { value: 'met', label: 'Atendido' },
                        { value: 'unmet', label: 'Não atendido' },
                        { value: 'not_applicable', label: 'Não se aplica' },
                    ]"
                /><template v-if="reviewForm.status === 'met'"
                    ><AppSelect
                        v-model="reviewForm.evidence_document_id"
                        required
                        label="Documento que comprova"
                        id="requirementreviewdialog-2"
                        :options="[
                            { value: '', label: 'Selecione' },
                            ...documents.map((d) => ({ value: d.id, label: d.name })),
                        ]" /><AppInput
                        v-model="reviewForm.evidence_page"
                        type="number"
                        min="1"
                        required
                        label="Página da evidência"
                        id="requirementreviewdialog-3" /></template
                ><AppTextarea
                    v-model="reviewForm.notes"
                    required
                    :maxlength="4000"
                    placeholder="Explique sua conclusão e os detalhes relevantes."
                    label="Fundamentação"
                    id="requirementreviewdialog-4"
                />
                <p v-if="error" role="alert" class="error">{{ error }}</p>
                <AppButton variant="primary" type="submit" class="primary" :disabled="busy"
                    >Salvar revisão</AppButton
                >
            </form></template
        ></AppDialog
    >
</template>
