<script setup>
import { AppButton, AppCard, AppTable } from '@/components/ui'
import {
    Radar,
    Search,
    Files,
    RefreshCw,
    ArrowUpRight,
    Check,
    Activity,
    FileText,
} from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
import { labels } from '@/constants/radar'
import { date, datetime } from '@/utils/formatters'
const { dashboard, perform, loadDashboard, go } = useWorkspace()
const historyColumns = [
    { key: 'id', label: 'Consulta' },
    { key: 'started_at', label: 'Início' },
    { key: 'imported', label: 'Novas' },
    { key: 'matched', label: 'Associações' },
    { key: 'status', label: 'Resultado' },
]
</script>

<template>
    <div class="page-heading">
        <div>
            <span class="eyebrow">VISÃO GERAL</span>
            <h1>Um radar. Novas possibilidades.</h1>
            <p>Acompanhe as oportunidades certas para os seus próximos projetos.</p>
        </div>
    </div>
    <section class="hero">
        <div>
            <span class="hero-tag"><span class="dot"></span>MONITORAMENTO DE LICITAÇÕES</span>
            <h2>Seu conhecimento técnico.<br />As oportunidades do Brasil.</h2>
            <p>
                Da busca à leitura dos editais, reúna tudo o que precisa<br class="desktop-break" />
                para decidir quais editais merecem a sua atenção.
            </p>
            <AppButton variant="ghost" @click="go('opportunities')">
                Explorar oportunidades<ArrowUpRight :size="18" />
            </AppButton>
        </div>
        <div class="hero-radar" aria-hidden="true">
            <span></span><span></span><span></span><Radar :size="70" /><i></i><b></b><em></em>
        </div>
    </section>
    <section class="stats">
        <AppCard as="article">
            <span><Search :size="18" />Oportunidades abertas</span
            ><strong>{{ dashboard.open || 0 }}</strong
            ><small>Entre os resultados importados</small>
        </AppCard>
        <AppCard as="article">
            <span><Check :size="18" />Aderentes ao escopo</span
            ><strong>{{ dashboard.relevant || 0 }}</strong
            ><small>Projetos e anteprojetos identificados</small>
        </AppCard>
        <AppCard as="article">
            <span><Files :size="18" />Acervo da empresa</span
            ><strong>{{ dashboard.documents || 0 }}</strong
            ><small>Certificações e atestados cadastrados</small>
        </AppCard>
    </section>
    <div class="dashboard-grid">
        <AppCard as="section" class="panel">
            <div class="panel-heading">
                <div>
                    <h3>Atividade do radar</h3>
                    <p>Histórico das consultas ao PNCP</p>
                </div>
                <AppButton variant="ghost" class="text-button" @click="perform(loadDashboard)">
                    <RefreshCw :size="15" />Atualizar
                </AppButton>
            </div>
            <div v-if="!dashboard.runs.length" class="empty">
                <Activity :size="32" />
                <h3>Vamos encontrar seu próximo projeto?</h3>
                <p>Acesse Oportunidades para selecionar os perfis de busca e consultar o PNCP.</p>
                <AppButton variant="outline" class="secondary" @click="go('opportunities')">
                    Ir para oportunidades<ArrowUpRight :size="16" />
                </AppButton>
            </div>
            <AppTable
                v-else
                :columns="historyColumns"
                :rows="dashboard.runs"
                caption="Histórico das consultas ao PNCP"
            >
                <template #cell-id="{ value }">Consulta #{{ value }}</template>
                <template #cell-started_at="{ value }">{{ datetime(value) }}</template>
                <template #cell-status="{ row }">
                    <span :class="['badge', row.status]">{{ labels[row.status] }}</span>
                    <small v-if="row.error" class="danger-text">{{ row.error }}</small>
                </template>
            </AppTable>
        </AppCard>
        <div class="right-stack">
            <AppCard as="section" class="panel">
                <div class="panel-heading">
                    <h3>Consultas manuais</h3>
                    <Search :size="19" />
                </div>
                <p>
                    Na tela Oportunidades, selecione os perfis de busca e clique em Consultar PNCP.
                    Os anexos são buscados quando você solicita no detalhe da oportunidade.
                </p>
                <AppButton variant="ghost" class="text-button" @click="go('profiles')">
                    Ajustar perfis<ArrowUpRight :size="15" />
                </AppButton>
            </AppCard>
            <AppCard as="section" class="panel">
                <h3>Seu acervo em dia</h3>
                <p v-if="!dashboard.expiring.length">
                    Cadastre a validade dos documentos para acompanhar vencimentos.
                </p>
                <div v-for="doc in dashboard.expiring" :key="doc.id" class="expiry">
                    <FileText :size="16" /><span
                        >{{ doc.name }}<small>Validade: {{ date(doc.expires_at) }}</small></span
                    >
                </div>
                <AppButton variant="ghost" class="text-button" @click="go('documents')">
                    Gerenciar documentos<ArrowUpRight :size="15" />
                </AppButton>
            </AppCard>
        </div>
    </div>
</template>
