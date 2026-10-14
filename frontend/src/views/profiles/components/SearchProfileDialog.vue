<script setup>
import { AppInput, AppSelect, AppCheckbox } from '@/components/forms'
import { AppButton } from '@/components/ui'
import AppDialog from '@/components/ui/AppDialog/index.vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { modalities } from '@/constants/radar'
const { busy, error, profileForm, saveProfile } = useWorkspace()
</script>

<template>
    <AppDialog
        :open="Boolean(profileForm)"
        :busy="busy"
        :title="profileForm?.id ? 'Editar perfil de busca' : 'Novo perfil de busca'"
        @close="profileForm = null"
        ><template v-if="profileForm"
            ><form @submit.prevent="saveProfile">
                <AppInput
                    v-model="profileForm.name"
                    required
                    :maxlength="120"
                    label="Nome do perfil"
                    id="searchprofiledialog-1"
                />
                <p>
                    A busca usa o acervo da empresa. Este perfil define filtros e o destinatário dos
                    resumos enviados pelo botão.
                </p>
                <AppInput
                    v-model="profileForm.keywords"
                    placeholder="Vazio = busca genérica"
                    label="Palavras-chave, separadas por vírgula"
                    id="searchprofiledialog-2"
                    hint="Busca qualquer um dos termos no objeto do edital, sem distinguir maiúsculas ou acentos. Vazio = todas as oportunidades compatíveis com o acervo."
                /><AppInput
                    v-model="profileForm.excluded_keywords"
                    placeholder="Vazio = nenhuma exclusão"
                    label="Palavras-chave a ignorar, separadas por vírgula"
                    id="searchprofiledialog-3"
                    hint="Ignora oportunidades cujo objeto contenha qualquer um dos termos, sem distinguir maiúsculas ou acentos. A exclusão tem prioridade sobre as palavras-chave de busca."
                /><AppInput
                    v-model="profileForm.states"
                    placeholder="Vazio = todo o Brasil"
                    label="UFs, separadas por vírgula"
                    id="searchprofiledialog-4"
                /><AppSelect
                    v-model="profileForm.modalities"
                    multiple
                    label="Modalidades"
                    id="searchprofiledialog-5"
                    hint="Nenhuma seleção = todas."
                    :options="modalities.map(([id, label]) => ({ value: id, label: label }))"
                />
                <div class="filter-grid">
                    <AppInput
                        v-model="profileForm.agency"
                        label="Órgão"
                        id="searchprofiledialog-6"
                    /><AppInput
                        v-model.number="profileForm.min_days"
                        type="number"
                        min="0"
                        max="365"
                        required
                        label="Dias mínimos para proposta"
                        id="searchprofiledialog-7"
                    /><AppInput
                        v-model="profileForm.min_value"
                        type="number"
                        min="0"
                        step="0.01"
                        label="Valor mínimo"
                        id="searchprofiledialog-8"
                    /><AppInput
                        v-model="profileForm.max_value"
                        type="number"
                        min="0"
                        step="0.01"
                        label="Valor máximo"
                        id="searchprofiledialog-9"
                    />
                </div>
                <AppInput
                    v-model="profileForm.email"
                    type="email"
                    placeholder="opcional"
                    label="E-mail para resumos manuais"
                    id="searchprofiledialog-10"
                /><AppCheckbox
                    class="checkbox"
                    v-model="profileForm.active"
                    label="Perfil ativo"
                    id="searchprofiledialog-11"
                />
                <p v-if="error" class="error" role="alert">{{ error }}</p>
                <AppButton variant="primary" type="submit" class="primary" :disabled="busy"
                    >Salvar perfil</AppButton
                >
            </form></template
        ></AppDialog
    >
</template>
