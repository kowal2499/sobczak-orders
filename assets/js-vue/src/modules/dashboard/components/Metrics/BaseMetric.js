import { deburr } from 'lodash'

export default {
    props: {
        data: {
            type: [Object, Array]
        },
        isBusy: {
            type: Boolean,
            default: false
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        searchKeys: {
            type: Array,
            default: () => (['customerName', 'orderNumber', 'productName'])
        }
    },

    computed: {
        filteredInnerData() {
            if (!this.q) {
                return this.innerData
            }

            const searchTerm = deburr(this.q).toLowerCase()

            return (this.innerData || []).filter(item =>
                (item._search || '').includes(searchTerm)
            )
        }
    },

    methods: {
        reset() {
            this.q = null
            this.innerData = []
        },

        addSearchKey(row) {
            return {
                ...row,
                _search: this.searchKeys.map(key => deburr(String(row[key] || '')).toLowerCase()).join(' ')
            }
        }
    },

    data: () => ({
        q: null,
        innerData: [],
    })
}