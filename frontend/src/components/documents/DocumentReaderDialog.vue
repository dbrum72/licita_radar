<script setup>
import AppDialog from '@/components/ui/AppDialog/index.vue'
import { useWorkspace } from '@/composables/useWorkspace'

const { busy, reading } = useWorkspace()
</script>

<template>
    <AppDialog
        :open="Boolean(reading)"
        :busy="busy"
        :title="reading?.name || 'Texto do PDF'"
        panel-class="reading"
        @close="reading = null"
        ><template v-if="reading"
            ><p v-if="reading.status !== 'ready'" class="alert">
                {{
                    reading.error ||
                    'Há páginas sem texto suficiente. Necessário OCR/revisão do PDF original.'
                }}
            </p>
            <article v-for="page in reading.pages" :key="page.number">
                <h3>Página {{ page.number }}</h3>
                <pre>{{ page.text || 'Sem texto extraível.' }}</pre>
            </article></template
        ></AppDialog
    >
</template>
