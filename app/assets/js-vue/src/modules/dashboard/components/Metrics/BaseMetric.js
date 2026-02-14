import { deburr } from 'lodash'
import ExcelExport from '@/services/ExcelExport/ExcelExport'

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
        },

        exportExcel(title, fields, data) {
            const worksheet = this.excel.addWorksheet(title, fields.filter(f => f.active))
            data.forEach(item => worksheet.addData(item))
            return this.excel.save().finally(() => this.excel.clear())
        }
    },

    data: () => ({
        q: null,
        innerData: [],
        excel: new ExcelExport()
    })
}