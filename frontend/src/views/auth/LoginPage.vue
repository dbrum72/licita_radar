<script setup>
import { AppInput } from '@/components/forms'
import { AppButton } from '@/components/ui'
import { ArrowUpRight } from '@lucide/vue'
import AuthShell from '@/components/public/AuthShell.vue'
import { useWorkspace } from '@/composables/useWorkspace'
const { setup, setupName, login, error, busy, isCoolingDown, retryMessage, signIn } = useWorkspace()
</script>
<template>
    <AuthShell
        ><section class="login-panel">
            <form @submit.prevent="signIn" class="login-box">
                <span class="eyebrow">SEU RADAR DE OPORTUNIDADES</span>
                <h2>{{ setup ? 'Configure seu acesso' : 'Bem-vindo ao LicitaRadar' }}</h2>
                <p>
                    {{
                        setup
                            ? 'Crie o primeiro usuário para começar.'
                            : 'Entre para acompanhar as oportunidades da empresa.'
                    }}
                </p>
                <AppInput
                    v-if="setup"
                    v-model="setupName"
                    required
                    :maxlength="120"
                    autocomplete="name"
                    label="Nome"
                    id="loginpage-1"
                /><AppInput
                    v-model="login.email"
                    type="email"
                    required
                    autocomplete="username"
                    label="E-mail"
                    id="loginpage-2"
                /><AppInput
                    v-model="login.password"
                    type="password"
                    required
                    :minlength="setup ? 12 : 1"
                    :autocomplete="setup ? 'new-password' : 'current-password'"
                    label="Senha"
                    id="loginpage-3"
                    :hint="setup ? 'Mínimo de 12 caracteres.' : ''"
                />
                <p v-if="error" role="alert" class="error">{{ error }}</p>
                <AppButton
                    variant="primary"
                    type="submit"
                    class="primary"
                    :disabled="busy || isCoolingDown('auth')"
                    :title="retryMessage('auth')"
                >
                    {{ busy ? 'Aguarde…' : setup ? 'Criar acesso' : 'Entrar no painel'
                    }}<ArrowUpRight :size="18" /></AppButton
                ><small>Documentos privados. Acesso restrito à sua equipe.</small>
            </form>
        </section>
    </AuthShell>
</template>
