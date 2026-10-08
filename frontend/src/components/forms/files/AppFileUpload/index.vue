<template>
    <div
        class="app-file-upload"
        :class="{
            'app-file-upload--invalid': hasError,
            'app-file-upload--disabled': disabled,
        }"
    >
        <label v-if="label" class="app-file-upload__label" :for="inputId">
            {{ label }}

            <span v-if="required" class="app-file-upload__required" aria-hidden="true"> * </span>
        </label>

        <input
            :id="inputId"
            ref="inputRef"
            class="app-file-upload__input"
            type="file"
            :name="name"
            :accept="accept || undefined"
            :multiple="multiple"
            :disabled="disabled"
            :required="required && modelValue.length === 0"
            :aria-invalid="hasError || undefined"
            :aria-describedby="ariaDescribedBy"
            @change="handleChange"
            @focus="emit('focus', $event)"
            @blur="emit('blur', $event)"
        />

        <button
            type="button"
            class="app-file-upload__browse"
            :disabled="disabled"
            @click="openFileDialog"
        >
            {{ browseLabel }}
        </button>

        <ul
            v-if="modelValue.length"
            class="app-file-upload__files"
            aria-label="Arquivos selecionados"
        >
            <li
                v-for="(file, index) in modelValue"
                :key="getFileKey(file, index)"
                class="app-file-upload__file"
            >
                <span class="app-file-upload__file-info">
                    <span class="app-file-upload__file-name">
                        {{ file.name }}
                    </span>

                    <span class="app-file-upload__file-size">
                        {{ formatFileSize(file.size) }}
                    </span>
                </span>

                <button
                    type="button"
                    class="app-file-upload__remove"
                    :aria-label="`${removeLabel}: ${file.name}`"
                    :disabled="disabled"
                    @click="removeFile(index)"
                >
                    {{ removeLabel }}
                </button>
            </li>
        </ul>

        <p v-else class="app-file-upload__empty">
            {{ emptyText }}
        </p>

        <p v-if="hint && !hasError" :id="hintId" class="app-file-upload__hint">
            {{ hint }}
        </p>

        <p v-if="hasError" :id="errorId" class="app-file-upload__error">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

import { appFileUploadProps } from './props.js'

const props = defineProps(appFileUploadProps)

const emit = defineEmits(['update:modelValue', 'select', 'remove', 'reject', 'focus', 'blur'])

const inputRef = ref(null)

const hasError = computed(() => Boolean(props.error))

const inputId = computed(() => (props.id ? `${props.id}-input` : undefined))

const hintId = computed(() => (props.id ? `${props.id}-hint` : undefined))

const errorId = computed(() => (props.id ? `${props.id}-error` : undefined))

const ariaDescribedBy = computed(() => {
    if (hasError.value) {
        return errorId.value
    }

    return props.hint ? hintId.value : undefined
})

function openFileDialog() {
    if (props.disabled) {
        return
    }

    inputRef.value?.click()
}

function handleChange(event) {
    const selectedFiles = Array.from(event.target.files ?? [])

    if (!selectedFiles.length) {
        resetNativeInput()
        return
    }

    const acceptedFiles = []

    for (const file of selectedFiles) {
        if (props.maxFileSize !== undefined && file.size > props.maxFileSize) {
            emit('reject', {
                file,
                reason: 'max-file-size',
            })

            continue
        }

        acceptedFiles.push(file)
    }

    if (!acceptedFiles.length) {
        resetNativeInput()
        return
    }

    if (!props.multiple) {
        const file = acceptedFiles[0]

        const nextValue = [file]

        emit('update:modelValue', nextValue)
        emit('select', nextValue)

        for (const rejectedFile of acceptedFiles.slice(1)) {
            emit('reject', {
                file: rejectedFile,
                reason: 'max-files',
            })
        }

        resetNativeInput()
        return
    }

    const availableSlots =
        props.maxFiles === undefined
            ? acceptedFiles.length
            : Math.max(props.maxFiles - props.modelValue.length, 0)

    const filesToAdd = acceptedFiles.slice(0, availableSlots)

    const rejectedFiles = acceptedFiles.slice(availableSlots)

    for (const file of rejectedFiles) {
        emit('reject', {
            file,
            reason: 'max-files',
        })
    }

    if (filesToAdd.length) {
        const nextValue = [...props.modelValue, ...filesToAdd]

        emit('update:modelValue', nextValue)
        emit('select', filesToAdd)
    }

    resetNativeInput()
}

function removeFile(index) {
    if (props.disabled) {
        return
    }

    const file = props.modelValue[index]

    if (!file) {
        return
    }

    const nextValue = props.modelValue.filter((_, fileIndex) => fileIndex !== index)

    emit('update:modelValue', nextValue)
    emit('remove', file)
}

function resetNativeInput() {
    if (inputRef.value) {
        inputRef.value.value = ''
    }
}

function getFileKey(file, index) {
    return [file.name, file.size, file.lastModified, index].join(':')
}

function formatFileSize(bytes) {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B'
    }

    if (bytes < 1024) {
        return `${bytes} B`
    }

    const kilobytes = bytes / 1024

    if (kilobytes < 1024) {
        return `${kilobytes.toFixed(1)} KB`
    }

    const megabytes = kilobytes / 1024

    return `${megabytes.toFixed(1)} MB`
}
</script>

<style src="./style.css"></style>
