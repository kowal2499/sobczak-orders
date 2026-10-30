const SHOWN = 3

export function describeSelection(label, values, options, name = o => o.label) {
    const names = values.map(value => {
        const option = options.find(o => o.value === value)
        return option ? name(option) : value
    })
    const rest = names.length > SHOWN ? ` +${names.length - SHOWN}` : ''

    return `${label}: ${names.slice(0, SHOWN).join(', ')}${rest}`
}
