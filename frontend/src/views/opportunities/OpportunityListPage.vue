<script setup>
import { AppInput, AppSelect, AppCheckbox } from '@/components/forms'
import { AppButton, AppCard } from '@/components/ui'
import ConsultationProfiles from '@/components/profiles/ConsultationProfiles.vue'
import {
    Search,
    RefreshCw,
    ArrowUpRight,
    Bookmark,
    ChevronLeft,
    ChevronRight,
    MapPin,
    Clock,
    Building2,
} from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { states, modalities, labels } from '@/constants/radar'
import { money, date } from '@/utils/formatters'
const {
    isCoolingDown,
    retryMessage,
    busy,
    results,
    filters,
    selectedIds,
    perform,
    loadResults,
    synchronize,
    openDetail,
    toggleBookmark,
    toggleDiscard,
    selectPage,
    exportSelected,
    openDocument,
    selectedProfileIds,
    consultationProfiles,
} = useWorkspace()
</script>

<template>
    <div class="page-heading">
        <div>
            <span class="eyebrow">BUSCA & SELEÇÃO</span>
            <h1>Oportunidades</h1>
            <p>
                Editais compatíveis com certidões, atestados, RRT e habilitação do CNPJ da empresa.
                Avalie as pendentes: salve as viáveis ou descarte as demais. Cada consulta
                acrescenta somente oportunidades novas; pendentes de consultas anteriores permanecem
                na lista.
            </p>
        </div>
        <AppButton
            variant="primary"
            class="primary"
            @click="synchronize"
            :disabled="busy || isCoolingDown('consultation')"
            :title="retryMessage('consultation')"
        >
            <RefreshCw :size="17" />{{
                retryMessage('consultation') || (busy ? 'Aguarde…' : 'Consultar PNCP')
            }}
        </AppButton>
    </div>
    <ConsultationProfiles
        v-model="selectedProfileIds"
        :profiles="consultationProfiles"
        :disabled="busy"
    />
    <AppCard as="form" class="panel filters" @submit.prevent="perform(() => loadResults())">
        <div class="filter-grid">
            <AppInput
                class="wide"
                v-model="filters.keyword"
                placeholder="Ex.: projeto arquitetônico"
                label="Palavra-chave"
                id="opportunitylistpage-1"
            /><AppSelect
                v-model="filters.state"
                label="UF"
                id="opportunitylistpage-2"
                :options="[
                    { value: '', label: 'Todo o Brasil' },
                    ...states.map((s) => ({ value: s, label: s })),
                ]"
            /><AppSelect
                v-model="filters.modality"
                label="Modalidade"
                id="opportunitylistpage-3"
                :options="[
                    { value: '', label: 'Todas' },
                    ...modalities.map(([id, label]) => ({ value: id, label: label })),
                ]"
            /><AppInput
                v-model="filters.agency"
                placeholder="Nome do órgão"
                label="Órgão"
                id="opportunitylistpage-4"
            /><AppInput
                v-model="filters.min_value"
                type="number"
                min="0"
                step="0.01"
                placeholder="R$"
                label="Valor mínimo"
                id="opportunitylistpage-5"
            /><AppInput
                v-model="filters.max_value"
                type="number"
                min="0"
                step="0.01"
                placeholder="R$"
                label="Valor máximo"
                id="opportunitylistpage-6"
            /><AppInput
                v-model="filters.deadline"
                type="date"
                label="Prazo até"
                id="opportunitylistpage-7"
            /><AppSelect
                v-model="filters.scope"
                label="Aderência"
                id="opportunitylistpage-8"
                :options="[
                    { value: '', label: 'Todos os resultados' },
                    { value: 'relevant', label: 'Aderentes ao escopo' },
                    { value: 'review', label: 'Revisar escopo' },
                    { value: 'excluded', label: 'Inclui obra' },
                ]"
            />
        </div>
        <div class="filter-footer">
            <div>
                <span class="meta">Somente editais com prazo aberto</span
                ><AppCheckbox
                    class="checkbox"
                    v-model="filters.bookmarked"
                    @update:model-value="filters.discarded = false"
                    label="Salvas"
                    id="opportunitylistpage-9"
                /><AppCheckbox
                    class="checkbox"
                    v-model="filters.discarded"
                    @update:model-value="filters.bookmarked = false"
                    label="Descartadas"
                    id="opportunitylistpage-10"
                />
            </div>
            <AppButton variant="primary" type="submit" class="primary" :disabled="busy"
                ><Search :size="16" />Aplicar filtros</AppButton
            >
        </div>
    </AppCard>
    <p class="panel">
        {{
            filters.discarded
                ? 'Descartadas com prazo aberto. A restauração é permitida até o vencimento; depois, o cadastro será excluído e não será reimportado.'
                : (filters.bookmarked
                      ? 'Oportunidades salvas para acompanhamento. '
                      : 'Oportunidades ainda não avaliadas, acumuladas entre consultas. ') +
                  (results.company_notice || '')
        }}
    </p>
    <AppCard as="div" v-if="!filters.discarded" class="panel export-toolbar">
        <strong>{{ selectedIds.length }} selecionadas para PDF <small>(máximo 50)</small></strong
        ><AppButton
            variant="outline"
            class="secondary"
            @click="selectPage"
            :disabled="busy || !results.data.length"
        >
            Selecionar esta página</AppButton
        ><AppButton
            variant="ghost"
            class="text-button"
            @click="selectedIds = []"
            :disabled="busy || !selectedIds.length"
        >
            Limpar seleção</AppButton
        ><AppButton
            variant="primary"
            class="primary"
            @click="exportSelected"
            :disabled="busy || !selectedIds.length"
        >
            Exportar selecionadas em PDF</AppButton
        ><small
            >A seleção é mantida ao trocar páginas e filtros. Recarregar a tela limpa a
            seleção.</small
        >
    </AppCard>
    <div class="result-heading">
        <strong>{{ results.total }} oportunidades</strong
        ><span>Fonte: Portal Nacional de Contratações Públicas</span>
    </div>
    <AppCard as="div" v-if="!results.data.length" class="panel empty">
        <Search :size="32" />
        <h3>Nenhum resultado por enquanto</h3>
        <p>
            Cadastre documentos válidos com texto extraído, consulte o PNCP e confira os filtros.
            Sem evidência documental, nenhuma oportunidade é recomendada.
        </p>
    </AppCard>
    <AppCard as="article" v-for="item in results.data" :key="item.id" class="opportunity-card">
        <div class="opportunity-top">
            <AppCheckbox
                v-if="!item.discarded"
                class="checkbox export-choice"
                :disabled="busy || (selectedIds.length >= 50 && !selectedIds.includes(item.id))"
                label="Selecionar para PDF"
                :id="'export-choice-' + item.id"
                :model-value="selectedIds.includes(item.id)"
                @update:model-value="
                    selectedIds = $event
                        ? [...selectedIds, item.id]
                        : selectedIds.filter((id) => id !== item.id)
                "
            /><span :class="['badge', item.scope_status]">{{ labels[item.scope_status] }}</span
            ><span class="meta">{{ item.modality }}</span
            ><AppButton
                variant="ghost"
                @click="toggleBookmark(item)"
                :aria-label="item.bookmarked ? 'Remover dos salvos' : 'Salvar oportunidade'"
                :class="{ saved: item.bookmarked }"
            >
                <Bookmark :size="20" :fill="item.bookmarked ? 'currentColor' : 'none'" />
            </AppButton>
        </div>
        <h3>
            <AppButton variant="ghost" @click="openDetail(item.id)">{{ item.object }}</AppButton>
        </h3>
        <p>{{ item.company_match?.reason }}</p>
        <details>
            <summary>Documentos que sustentam a compatibilidade</summary>
            <div
                v-for="e in item.company_match?.evidence"
                :key="e.document_id + '-' + e.page"
                class="candidate"
            >
                <AppButton
                    variant="ghost"
                    class="text-button"
                    @click="openDocument({ id: e.document_id })"
                >
                    {{ e.name }} · página {{ e.page }}
                </AppButton>
                <p>{{ e.concepts.join(', ') }}</p>
                <p>{{ e.excerpt }}</p>
            </div>
        </details>
        <p class="agency"><Building2 :size="15" />{{ item.agency }}</p>
        <div class="opportunity-bottom">
            <span><MapPin :size="15" />{{ item.city }} · {{ item.state }}</span
            ><span><Clock :size="15" />{{ date(item.deadline) }}</span
            ><strong>{{ money(item.estimated_value) }}</strong
            ><AppButton variant="ghost" class="text-button" @click="openDetail(item.id)">
                Analisar edital<ArrowUpRight :size="16" /></AppButton
            ><AppButton
                variant="outline"
                class="secondary"
                @click="toggleDiscard(item)"
                :disabled="busy"
            >
                {{ item.discarded ? 'Restaurar' : 'Descartar' }}
            </AppButton>
        </div>
    </AppCard>
    <div v-if="results.last_page > 1" class="pagination">
        <AppButton
            variant="ghost"
            :disabled="results.current_page === 1 || busy"
            @click="perform(() => loadResults(results.current_page - 1))"
        >
            loadResults(results.current_page - 1))" > <ChevronLeft :size="18" />Anterior</AppButton
        ><span>{{ results.current_page }} / {{ results.last_page }}</span
        ><AppButton
            variant="ghost"
            :disabled="results.current_page === results.last_page || busy"
            @click="perform(() => loadResults(results.current_page + 1))"
        >
            loadResults(results.current_page + 1))" > Próxima<ChevronRight :size="18" />
        </AppButton>
    </div>
</template>
