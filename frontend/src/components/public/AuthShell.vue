<script setup>
import { Radar } from '@lucide/vue'
</script>
<template>
    <div class="login-page">
        <section class="login-story">
            <div class="brand">
                <Radar :size="32" /><span>Licita<strong>Radar</strong></span>
            </div>
            <span class="eyebrow">ENGENHARIA & ARQUITETURA</span>
            <h1>O próximo projeto<br />começa aqui.</h1>
            <p>
                Encontre oportunidades públicas, organize seus atestados e avalie cada exigência com
                clareza.
            </p>
            <div class="radar-art"><i></i><i></i><i></i><span></span><Radar :size="72" /></div>
            <small>Projetos e anteprojetos · Todo o Brasil · PNCP</small>
        </section>
        <slot />
    </div>
</template>
