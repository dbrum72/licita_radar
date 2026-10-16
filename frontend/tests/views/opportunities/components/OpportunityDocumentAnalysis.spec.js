import { expect, it } from 'vitest'
import OpportunityDocumentAnalysis from '@/views/opportunities/components/OpportunityDocumentAnalysis.vue'
import { button, mountWorkspace } from '../../../helpers/workspace'

function report() {
    const reference = {
        source_key: 'company-1',
        source_name: 'atestado.pdf',
        page: 2,
        quote: 'Projeto de 150 m2',
        verification: 'text_match',
    }
    return {
        generated_at: '2026-10-14T12:00:00-03:00',
        model: 'gpt-5.4-mini',
        opportunity: { deadline_pncp: '2026-10-25T10:00:00-03:00' },
        notice: 'Revise os documentos originais.',
        sources: [{ key: 'company-1', name: 'atestado.pdf', scope: 'company', reading: 'text' }],
        usage: { input_tokens: 1000, output_tokens: 200, estimated_cost_usd: 0.00158 },
        analysis: {
            summary: 'Confronto documental preliminar',
            scope: {
                status: 'aderente',
                reason: 'Projeto arquitetônico documentado.',
                sources: [reference],
            },
            deadlines: [],
            qualifications: [
                {
                    name: 'Atestado técnico',
                    category: 'tecnica_operacional',
                    holder: 'Empresa Exemplo',
                    valid_until: null,
                    specialties: ['Arquitetura'],
                    quantities: '150 m2',
                    sources: [reference],
                },
            ],
            requirements: [
                {
                    description: 'Atestado de projeto de 100 m2',
                    category: 'tecnica_operacional',
                    status: 'parcial',
                    mandatory: true,
                    reason: 'Vínculo do profissional precisa ser conferido.',
                    required_quantities: '100 m2',
                    sources: [],
                    evidence: [reference],
                    gaps: ['Comprovar o vínculo profissional.'],
                },
            ],
            warnings: ['Arquivo ausente: retificação.pdf'],
        },
    }
}

it('não analisa automaticamente e explica a configuração ausente', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityDocumentAnalysis)
    expect(state.generateAnalysis).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('OPENAI_API_KEY')
    expect(button(wrapper, 'Analisar documentação').element.disabled).toBe(true)
    state.analysis.value.configured = true
    await wrapper.vm.$nextTick()
    await button(wrapper, 'Analisar documentação').trigger('click')
    expect(state.generateAnalysis).toHaveBeenCalledOnce()
})

it('mostra exigências, classificações, lacunas, custo e referências sem tratar como aprovação', async () => {
    const { wrapper, state } = mountWorkspace(OpportunityDocumentAnalysis)
    state.analysis.value = { configured: true, report: report(), stale: true }
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('Relatório desatualizado')
    expect(wrapper.text()).toContain('Técnica operacional')
    expect(wrapper.text()).toContain('Comprovar o vínculo profissional')
    expect(wrapper.text()).toContain('Arquivo ausente: retificação.pdf')
    expect(wrapper.text()).toContain('US$ 0.0016')
    expect(wrapper.text()).toContain('atestado.pdf · página 2')
    await wrapper.get('a').trigger('click')
    expect(state.openAnalysisSource).toHaveBeenCalledWith(
        expect.objectContaining({ source_key: 'company-1', page: 2 }),
    )
    await button(wrapper, 'Baixar relatório PDF').trigger('click')
    expect(state.downloadAnalysis).toHaveBeenCalledOnce()
    await button(wrapper, 'Atualizar relatório salvo').trigger('click')
    expect(state.refreshAnalysis).toHaveBeenCalledOnce()
})
