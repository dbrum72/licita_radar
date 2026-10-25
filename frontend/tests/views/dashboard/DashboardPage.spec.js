import { expect, it } from 'vitest'
import DashboardPage from '@/views/dashboard/DashboardPage.vue'
import { button, mountWorkspace } from '../../helpers/workspace'

it('encaminha a consulta para Oportunidades e permite atualizar apenas o histórico', async () => {
    const { wrapper, state } = mountWorkspace(DashboardPage)
    expect(
        wrapper.findAll('button').some((button) => button.text().includes('Consultar PNCP')),
    ).toBe(false)
    await button(wrapper, 'Ir para oportunidades').trigger('click')
    expect(state.go).toHaveBeenCalledWith('opportunities')
    await button(wrapper, 'Atualizar').trigger('click')
    expect(state.loadDashboard).toHaveBeenCalledOnce()
    expect(state.synchronize).not.toHaveBeenCalled()
})

it('mostra falhas no histórico sem oferecer uma consulta direta', async () => {
    const { wrapper, state } = mountWorkspace(DashboardPage)
    state.dashboard.value.runs = [
        { id: 7, status: 'failed', error: 'PNCP indisponível', imported: 0, matched: 0 },
    ]
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('PNCP indisponível')
    expect(
        wrapper
            .findAll('button')
            .some((button) => /Consultar PNCP|Iniciar consulta/.test(button.text())),
    ).toBe(false)
    expect(state.synchronize).not.toHaveBeenCalled()
})
