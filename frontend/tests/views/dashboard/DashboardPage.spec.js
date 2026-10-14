import { expect, it } from 'vitest'
import DashboardPage from '@/views/dashboard/DashboardPage.vue'
import { button, mountWorkspace } from '../../helpers/workspace'

it('mantém a consulta PNCP manual e a atualização do histórico explícita', async () => {
    const { wrapper, state } = mountWorkspace(DashboardPage)
    expect(state.synchronize).not.toHaveBeenCalled()
    await button(wrapper, 'Consultar PNCP').trigger('click')
    expect(state.synchronize).toHaveBeenCalledOnce()
    await button(wrapper, 'Atualizar').trigger('click')
    expect(state.loadDashboard).toHaveBeenCalledOnce()
})

it('mostra falhas de consulta e respeita a espera da ação', async () => {
    const { wrapper, state } = mountWorkspace(DashboardPage)
    state.dashboard.value.runs = [
        { id: 7, status: 'failed', error: 'PNCP indisponível', imported: 0, matched: 0 },
    ]
    state.isCoolingDown.mockReturnValue(true)
    state.retryMessage.mockReturnValue('Aguarde 20 segundos')
    state.busy.value = true
    await wrapper.vm.$nextTick()
    state.busy.value = false
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain('PNCP indisponível')
    const consult = button(wrapper, 'Aguarde 20 segundos')
    expect(consult.element.disabled).toBe(true)
    await consult.trigger('click')
    expect(state.synchronize).not.toHaveBeenCalled()
})
