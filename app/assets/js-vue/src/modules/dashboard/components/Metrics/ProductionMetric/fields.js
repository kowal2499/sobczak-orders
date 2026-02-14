import Field from '@/services/Field/Field'
import { getUserDepartments } from '@/helpers'
import { getFactorName, getFactorValue } from '@/modules/dashboard/services/FactorHelper'

export default [
    new Field({
        title: 'Klient',
        path: 'customerName'
    }),
    new Field({
        title: 'Produkt',
        path: 'productName',
    }),
    new Field({
        title: 'Numer zamówienia',
        path: 'orderNumber',
    }),
    new Field({
        title: 'Współczynnik',
        path: 'factor'
    }),
    ...getUserDepartments().map(dpt => new Field({
        title: dpt.name,
        path: `involved_${dpt.slug}.factor`,
        meta: {
            excel: {
                noteGetter: function(record) {
                    const path = `involved_${dpt.slug}.factorsStack`
                    const factorsStack = this.defaultGetter(record, path) || []

                    return factorsStack
                        .map(item => [
                            { name: 'źródło', value: getFactorName(item.source, item.value) },
                            { name: 'wartość', value: getFactorValue(item.source, item.value) },
                            { name: 'opis', value: item.description },
                        ]
                            .filter(item => item.value !== null)
                            .map(item => `${item.name}: ${item.value}`)
                            .join('\n')
                        ).join('\n\n')
                }
            }
        }
    }))
]