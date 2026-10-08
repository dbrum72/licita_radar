<script setup>
import { AppButton } from '@/components/ui'
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'
import { X } from '@lucide/vue'
import dialogProps from './props'

const props = defineProps(dialogProps)
const emit = defineEmits(['close'])
const panel = ref(null)
const titleId = 'dialog-' + useId()
let previousFocus
let generation = 0
const selector =
    'button:not([disabled]),a[href],input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex="0"]'

function close() {
    if (!props.busy) emit('close')
}
function keydown(event) {
    const dialogs = document.querySelectorAll('[role="dialog"][aria-modal="true"]')
    if (dialogs[dialogs.length - 1] !== panel.value) return
    if (event.key === 'Escape') {
        event.preventDefault()
        close()
    }
    if (event.key !== 'Tab') return
    const controls = [...panel.value.querySelectorAll(selector)].filter(
        (element) => !element.closest('[hidden],[inert]') && element.tabIndex >= 0,
    )
    const first = controls[0],
        last = controls[controls.length - 1]
    if (!first) {
        event.preventDefault()
        panel.value.focus()
        return
    }
    if (
        event.shiftKey &&
        (document.activeElement === first || !panel.value.contains(document.activeElement))
    ) {
        event.preventDefault()
        last.focus()
    } else if (
        !event.shiftKey &&
        (document.activeElement === last || !panel.value.contains(document.activeElement))
    ) {
        event.preventDefault()
        first.focus()
    }
}
watch(
    () => props.open,
    async (open) => {
        if (typeof document === 'undefined') return
        const current = ++generation
        document.removeEventListener('keydown', keydown)
        if (open) {
            previousFocus = document.activeElement
            document.addEventListener('keydown', keydown)
            await nextTick()
            if (current === generation && props.open)
                (
                    panel.value?.querySelector('[autofocus]') ||
                    panel.value?.querySelector(selector) ||
                    panel.value
                )?.focus()
        } else {
            await nextTick()
            if (current === generation && previousFocus?.isConnected) previousFocus.focus()
            previousFocus = null
        }
    },
    { immediate: true },
)
onBeforeUnmount(() => {
    generation++
    document.removeEventListener('keydown', keydown)
    if (previousFocus?.isConnected) previousFocus.focus()
})
</script>

<template>
    <div v-if="open" class="modal-overlay" @click.self="close">
        <section
            ref="panel"
            class="modal"
            :class="panelClass"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="titleId"
            tabindex="-1"
        >
            <div class="panel-heading">
                <h2 :id="titleId">{{ title }}</h2>
                <AppButton
                    variant="ghost"
                    type="button"
                    :disabled="busy"
                    aria-label="Fechar"
                    @click="close"
                >
                    <X :size="22" />
                </AppButton>
            </div>
            <slot />
        </section>
    </div>
</template>
