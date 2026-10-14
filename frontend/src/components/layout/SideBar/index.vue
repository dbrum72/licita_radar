<script setup>
import { AppButton } from '@/components/ui'
import { Radar, Building2, LogOut } from '@lucide/vue'
import { SideBarNav } from '@/components/navigation'
defineProps({
    user: { type: Object, required: true },
    dashboard: { type: Object, required: true },
    nav: { type: Array, required: true },
    view: String,
    busy: Boolean,
})
defineEmits(['navigate', 'logout'])
</script>
<template>
    <aside class="sidebar">
        <a class="brand" href="#" @click.prevent="!busy && $emit('navigate', 'dashboard')"
            ><span class="brand-icon"><Radar :size="26" /></span
            ><span>Licita<strong>Radar</strong><small>INTELIGÊNCIA EM PROJETOS</small></span></a
        >
        <div class="workspace">
            <span class="workspace-icon"><Building2 :size="18" /></span>
            <div><strong>Minha empresa</strong><small>Engenharia e arquitetura</small></div>
            <span class="dot"></span>
        </div>
        <span class="nav-caption">ESPAÇO DE TRABALHO</span>
        <SideBarNav
            :items="nav"
            :active-id="view"
            :opportunity-count="dashboard.open || 0"
            :busy="busy"
            @navigate="$emit('navigate', $event)"
        />
        <div class="sidebar-note">
            <Radar :size="24" /><strong>Foco em quem projeta.</strong>
            <p>Projetos e anteprojetos em todo o país. Objetos com obras são sinalizados.</p>
            <span>FONTE OFICIAL · PNCP</span>
        </div>
        <div class="account">
            <span class="avatar">{{ user.name?.charAt(0) }}</span>
            <div>
                <strong>{{ user.name }}</strong
                ><small>{{ user.email }}</small>
            </div>
            <AppButton variant="ghost" @click="$emit('logout')" :disabled="busy" aria-label="Sair">
                <LogOut :size="18" />
            </AppButton>
        </div>
    </aside>
</template>
