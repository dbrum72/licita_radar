import { inject, provide } from 'vue'
import { useLicitaRadar } from '@/composables/useLicitaRadar'
const workspaceKey = Symbol('LicitaRadar')
export function provideWorkspace(workspace = useLicitaRadar()) {
    provide(workspaceKey, workspace)
    return workspace
}
export function useWorkspace() {
    const workspace = inject(workspaceKey)
    if (!workspace) throw new Error('Workspace provider is required')
    return workspace
}
