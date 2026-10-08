<script setup>
import { AppButton, AppCard } from '@/components/ui'
import SearchProfileDialog from './components/SearchProfileDialog.vue'
import { SlidersHorizontal, Mail, Plus, MapPin, Clock } from '@lucide/vue'
import { useWorkspace } from '@/composables/useWorkspace'
const { isCoolingDown, retryMessage, busy, profiles, sendDigest, editProfile, toggleProfile } =
    useWorkspace()
</script>

<template>
    <div class="page-heading">
        <div>
            <span class="eyebrow">MONITORAMENTO</span>
            <h1>Perfis de busca</h1>
            <p>
                Refine oportunidades compatíveis com o acervo e defina quem receberá as novidades.
            </p>
        </div>
        <AppButton
            variant="outline"
            class="secondary"
            @click="sendDigest"
            :disabled="busy || isCoolingDown('digest')"
            :title="retryMessage('digest')"
        >
            <Mail :size="17" />Enviar resumos por e-mail</AppButton
        ><AppButton variant="primary" class="primary" @click="editProfile()"
            ><Plus :size="17" />Novo perfil</AppButton
        >
    </div>
    <div class="profile-grid">
        <AppCard as="article" v-for="p in profiles" :key="p.id" class="panel profile-card">
            <div class="panel-heading">
                <span class="profile-icon"><SlidersHorizontal :size="22" /></span
                ><span :class="['badge', p.active ? 'ready' : 'pending']">{{
                    p.active ? 'Ativo' : 'Pausado'
                }}</span>
            </div>
            <h3>{{ p.name }}</h3>
            <p>
                {{
                    p.keywords?.length
                        ? 'Palavras-chave: ' + p.keywords.join(', ')
                        : 'Busca genérica'
                }}
            </p>
            <p v-if="p.excluded_keywords?.length">Ignorar: {{ p.excluded_keywords.join(', ') }}</p>
            <p>Base da busca: documentação da empresa</p>
            <p>
                <MapPin :size="15" />{{ p.states?.length ? p.states.join(', ') : 'Todo o Brasil' }}
            </p>
            <p><Clock :size="15" />Pelo menos {{ p.min_days }} dias para proposta</p>
            <p><Mail :size="15" />{{ p.email || 'Sem destinatário de e-mail' }}</p>
            <div class="profile-actions">
                <AppButton variant="outline" class="secondary" @click="editProfile(p)"
                    >Editar perfil</AppButton
                ><AppButton variant="ghost" class="text-button" @click="toggleProfile(p)">
                    {{ p.active ? 'Pausar' : 'Ativar' }}
                </AppButton>
            </div>
        </AppCard>
    </div>
    <SearchProfileDialog />
</template>
