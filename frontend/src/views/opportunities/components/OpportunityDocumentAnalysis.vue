<script setup>
import { computed } from 'vue'
import { AppCard, AppButton } from '@/components/ui'
import { useWorkspace } from '@/composables/useWorkspace'
import { datetime } from '@/utils/formatters'
import AnalysisCitations from './AnalysisCitations.vue'
const {
    analysis,
    busy,
    generatingAnalysis,
    generateAnalysis,
    refreshAnalysis,
    downloadAnalysis,
    isCoolingDown,
    retryMessage,
    detail,
} = useWorkspace()
const report = computed(() => analysis.value.report)
const result = computed(() => report.value?.analysis)
const labels = {
    comprovada: 'Comprovada',
    parcial: 'Parcial',
    nao_comprovada: 'Não comprovada',
    inconclusiva: 'Inconclusiva',
    aderente: 'Aderente',
    nao_aderente: 'Não aderente',
    inconclusivo: 'Inconclusivo',
    tecnica_profissional: 'Técnica profissional',
    tecnica_operacional: 'Técnica operacional',
    registro_certificacao: 'Registro / certificação',
    juridica: 'Jurídica',
    fiscal: 'Fiscal',
    economico_financeira: 'Econômico-financeira',
    outros: 'Outros',
    text: 'Texto lido em memória',
    visual: 'Leitura visual',
    missing: 'Arquivo ausente',
    invalid: 'Conteúdo não PDF',
}
</script>
<template>
    <AppCard as="section" class="panel document-analysis">
        <div class="panel-heading">
            <div>
                <h3>Relatório documental por IA</h3>
                <p>Exigências do edital confrontadas com os documentos da empresa.</p>
            </div>
            <AppButton
                variant="primary"
                :disabled="
                    busy ||
                    !analysis.configured ||
                    isCoolingDown('analysis:' + detail.opportunity.id)
                "
                :title="retryMessage('analysis:' + detail.opportunity.id)"
                @click="generateAnalysis"
            >
                {{
                    generatingAnalysis
                        ? 'Analisando documentos…'
                        : report
                          ? 'Atualizar análise'
                          : 'Analisar documentação'
                }}
            </AppButton>
        </div>
        <p v-if="!analysis.configured" class="alert">
            A integração de IA ainda não está configurada. Defina OPENAI_API_KEY no backend para
            gerar o relatório.
        </p>
        <p class="meta">
            A análise envia o conteúdo necessário dos PDFs à OpenAI e pode consumir créditos. Um
            relatório atual é reutilizado sem nova chamada. Os arquivos originais permanecem nas
            pastas atuais.
        </p>
        <AppButton variant="ghost" class="text-button" :disabled="busy" @click="refreshAnalysis"
            >Atualizar relatório salvo</AppButton
        >
        <p v-if="!report">
            Baixe os anexos e cadastre o acervo da empresa para gerar uma análise com referências
            aos arquivos e páginas.
        </p>
        <template v-else>
            <p v-if="analysis.stale" class="alert">
                Relatório desatualizado: documentos, dados ou data da análise mudaram. Atualize a
                análise antes de decidir.
            </p>
            <p class="alert">{{ report.notice }}</p>
            <div class="analysis-toolbar">
                <span class="meta"
                    >Gerado em {{ datetime(report.generated_at) }} · {{ report.model }}</span
                >
                <AppButton variant="outline" :disabled="busy" @click="downloadAnalysis"
                    >Baixar relatório PDF</AppButton
                >
            </div>
            <p>{{ result.summary }}</p>
            <h4>Aderência ao escopo: {{ labels[result.scope.status] }}</h4>
            <p>{{ result.scope.reason }}</p>
            <AnalysisCitations :references="result.scope.sources" />
            <div v-if="result.warnings.length" class="analysis-warnings">
                <h4>Pendências e limitações</h4>
                <ul>
                    <li v-for="(warning, index) in result.warnings" :key="index">{{ warning }}</li>
                </ul>
            </div>
            <h4>Prazos identificados nos documentos</h4>
            <p class="meta">
                Prazo PNCP cadastrado:
                {{
                    report.opportunity.deadline_pncp
                        ? datetime(report.opportunity.deadline_pncp)
                        : 'Não informado'
                }}. Confira eventuais divergências e retificações.
            </p>
            <article
                v-for="(deadline, index) in result.deadlines"
                :key="index"
                class="analysis-item"
            >
                <strong>{{ deadline.label }} — {{ deadline.value }}</strong>
                <p>{{ deadline.notes }}</p>
                <AnalysisCitations :references="deadline.sources" />
            </article>
            <p v-if="!result.deadlines.length">
                Nenhum prazo documental identificado. Confira os PDFs originais.
            </p>
            <h4>Classificação do acervo da empresa</h4>
            <article
                v-for="(qualification, index) in result.qualifications"
                :key="index"
                class="analysis-item"
            >
                <strong>{{ qualification.name }}</strong>
                <p>
                    {{ labels[qualification.category] }} · Titular:
                    {{ qualification.holder || 'Não identificado' }} · Validade:
                    {{ qualification.valid_until || 'Não identificada' }}
                </p>
                <p>
                    Especialidades:
                    {{ qualification.specialties.join(', ') || 'Não identificadas' }}
                </p>
                <p>Quantitativos: {{ qualification.quantities || 'Não identificados' }}</p>
                <AnalysisCitations :references="qualification.sources" />
            </article>
            <p v-if="!result.qualifications.length">Nenhuma habilitação classificada.</p>
            <h4>Exigências e confronto documental</h4>
            <article
                v-for="(requirement, index) in result.requirements"
                :key="index"
                class="analysis-item"
            >
                <strong>{{ index + 1 }}. {{ requirement.description }}</strong>
                <p>
                    <span
                        :class="[
                            'badge',
                            requirement.status === 'comprovada' ? 'stored' : 'pending',
                        ]"
                        >{{ labels[requirement.status] }}</span
                    >
                    · {{ labels[requirement.category] }} ·
                    {{
                        requirement.mandatory
                            ? 'Obrigatória conforme documentos'
                            : 'Obrigatoriedade a conferir'
                    }}
                </p>
                <p>{{ requirement.reason }}</p>
                <p v-if="requirement.required_quantities">
                    Quantitativos exigidos: {{ requirement.required_quantities }}
                </p>
                <h5>Origem no processo</h5>
                <AnalysisCitations :references="requirement.sources" />
                <h5>Evidências da empresa</h5>
                <AnalysisCitations :references="requirement.evidence" />
                <template v-if="requirement.gaps.length"
                    ><h5>Lacunas e ações</h5>
                    <ul>
                        <li v-for="(gap, gapIndex) in requirement.gaps" :key="gapIndex">
                            {{ gap }}
                        </li>
                    </ul></template
                >
            </article>
            <p v-if="!result.requirements.length">
                Nenhuma exigência identificada. Resultado inconclusivo; revise integralmente o
                edital.
            </p>
            <details>
                <summary>Arquivos considerados ({{ report.sources.length }})</summary>
                <ul>
                    <li v-for="source in report.sources" :key="source.key">
                        {{ source.name }} ·
                        {{ source.scope === 'company' ? 'Acervo' : 'Processo' }} ·
                        {{ labels[source.reading] }}
                    </li>
                </ul>
            </details>
            <p v-if="report.usage" class="meta">
                Consumo: {{ report.usage.input_tokens }} tokens de entrada e
                {{ report.usage.output_tokens }} de saída.
                <template v-if="report.usage.estimated_cost_usd !== null"
                    >Custo estimado: US$ {{ report.usage.estimated_cost_usd.toFixed(4) }} pelas
                    tarifas configuradas, sujeito à cobrança efetiva.</template
                ><template v-else>Custo não calculado para este modelo.</template>
            </p>
        </template>
    </AppCard>
</template>
<style scoped>
.document-analysis h4 {
    margin-top: 24px;
}
.analysis-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.analysis-item {
    padding: 16px 0;
    border-bottom: 1px solid var(--color-border);
    overflow-wrap: anywhere;
}
.analysis-item h5 {
    margin: 14px 0 6px;
}
.analysis-warnings {
    padding: 12px 16px;
    background: var(--color-surface-muted);
    border-radius: 6px;
}
.document-analysis ul {
    padding-left: 20px;
}
.document-analysis li {
    margin: 6px 0;
}
:deep(.analysis-citation) {
    padding: 10px 12px;
    margin: 10px 0;
    border-left: 3px solid var(--color-brand);
    background: var(--color-surface-muted);
    overflow-wrap: anywhere;
}
:deep(.analysis-citation p) {
    margin: 6px 0;
    white-space: pre-wrap;
}
summary {
    cursor: pointer;
    margin: 20px 0;
}
</style>
