import { get } from 'lodash'

export const TYPE_DATE = 'date'

export default class Field {
    constructor(options = {}) {
        this.title = options.title || ''
        this.type = options.type || 'string'
        this.path = options.path || ''
        this.getter = typeof options.getter === 'function' ? options.getter : this.defaultGetter
        this.text = options.text || null
        this.active = options.active !== undefined ? options.active : true
        this.meta = options.meta || {}
    }

    getValue(data) {
        return this.getter(data)
    }

    defaultGetter(data, path = null) {
        const value = get(data, path || this.path)

        return this.normalizeValue(value)
    }

    normalizeValue(value) {
        if (this.type === TYPE_DATE && value) {
            return new Date(value)
        }
        return value
    }
}