<script setup>
import { AppCheckbox } from '@/components/forms'
import { AppCard } from '@/components/ui'

const props = defineProps({
    profiles: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])
function select(id, checked) {
    emit(
        'update:modelValue',
        checked
            ? [...new Set([...props.modelValue, id])]
            : props.modelValue.filter((selected) => selected !== id),
    )
}
</script>

<template>
    <AppCard as="section" class="panel" aria-label="Perfis da próxima consulta">
        <h3>Perfis da próxima consulta</h3>
        <p>
            Marque os perfis que deseja aplicar ao consultar PNCP. Cada oportunidade deve atender a
            todos os selecionados, mantendo a compatibilidade com o acervo.
        </p>
        <div class="consultation-profiles">
            <AppCheckbox
                v-for="profile in profiles"
                :key="profile.id"
                :id="`consultation-profile-${profile.id}`"
                :label="profile.name"
                :model-value="modelValue.includes(profile.id)"
                :disabled="disabled"
                @update:model-value="select(profile.id, $event)"
            />
        </div>
        <p v-if="!profiles.length">Não há perfis ativos disponíveis.</p>
        <p v-if="!modelValue.length">
            Nenhum perfil selecionado: consulta somente pelo acervo, sem filtros de perfil.
        </p>
        <p class="meta">
            A seleção vale para novas oportunidades. Editais já consultados não serão reavaliados ao
            trocar os perfis.
        </p>
    </AppCard>
</template>

<style scoped>
.consultation-profiles {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 24px;
    margin: 16px 0;
}
</style>
