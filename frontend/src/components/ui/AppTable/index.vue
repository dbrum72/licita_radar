<template>
    <div class="app-table-container">
        <table class="app-table">
            <caption v-if="caption" class="app-table__caption">
                {{
                    caption
                }}
            </caption>

            <thead class="app-table__head">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        scope="col"
                        class="app-table__header"
                        :class="alignmentClass(column)"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>

            <tbody class="app-table__body">
                <template v-if="rows.length">
                    <tr v-for="row in rows" :key="row[rowKey]" class="app-table__row">
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="app-table__cell"
                            :class="alignmentClass(column)"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ row[column.key] ?? '—' }}
                            </slot>
                        </td>
                    </tr>
                </template>

                <tr v-else>
                    <td :colspan="columns.length" class="app-table__empty">
                        <slot name="empty">
                            {{ emptyText }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { appTableProps } from './props.js'

defineProps(appTableProps)

function alignmentClass(column) {
    const alignment = column.align ?? 'start'

    return `app-table--align-${alignment}`
}
</script>

<style src="./style.css"></style>
