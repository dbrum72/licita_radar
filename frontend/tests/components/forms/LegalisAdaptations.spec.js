import { expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { AppInput, AppSelect } from '@/components/forms'

it('preserva modalidades numéricas no seletor múltiplo e permite limpar seleção', async () => {
    const wrapper = mount(AppSelect, {
        props: {
            multiple: true,
            modelValue: [4],
            id: 'modalities',
            options: [
                { value: 4, label: 'Concorrência' },
                { value: 6, label: 'Pregão' },
            ],
        },
    })
    expect(wrapper.get('select').element.options[0].selected).toBe(true)
    await wrapper.get('select').setValue(['4', '6'])
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([[4, 6]])
    await wrapper.setProps({ modelValue: [] })
    expect([...wrapper.get('select').element.options].every((option) => !option.selected)).toBe(
        true,
    )
})

it('preserva o modificador number sem converter um campo vazio em zero', async () => {
    const wrapper = mount(AppInput, {
        props: { type: 'number', modelModifiers: { number: true }, id: 'days' },
    })
    await wrapper.get('input').setValue('3')
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([3])
    await wrapper.get('input').setValue('')
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([''])
})
