// @vitest-environment node
import { expect, it } from 'vitest'
import { readdirSync, readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { parse } from '@vue/compiler-sfc'
import { baseParse } from '@vue/compiler-dom'

function pages(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) =>
        entry.isDirectory()
            ? pages(directory + '/' + entry.name)
            : entry.name.endsWith('.vue')
              ? [directory + '/' + entry.name]
              : [],
    )
}

it('mantém controles nativos encapsulados nos componentes em vez de reintroduzi-los nas páginas', () => {
    const directory = fileURLToPath(new URL('../../src/views', import.meta.url))
    const violations = []
    for (const file of pages(directory)) {
        const template = parse(readFileSync(file, 'utf8')).descriptor.template.content
        const visit = (node) => {
            if (
                node.type === 1 &&
                ['input', 'select', 'textarea', 'button', 'label'].includes(node.tag)
            )
                violations.push(file + ': ' + node.tag)
            for (const child of node.children || []) visit(child)
        }
        visit(baseParse(template))
    }
    expect(violations).toEqual([])
})
