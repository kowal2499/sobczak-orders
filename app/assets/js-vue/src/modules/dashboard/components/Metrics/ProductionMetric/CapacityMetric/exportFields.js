import { getFactorName, getFactorValue } from '@/modules/dashboard/services/FactorHelper'
import Field, { TYPE_DATE } from '@/services/Field/Field'
import { statuses, DEPARTMENTS } from '@/helpers'

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
        title: 'Dział',
        path: 'data.production.departmentSlug',
        getter: record => {
            const dpt = DEPARTMENTS.find(s => s.slug === record.data.production.departmentSlug)
            return dpt ? dpt.name : record.data.production.departmentSlug
        }
    }),
    new Field({
        title: 'Data rozpoczęcia',
        path: 'data.production.dateStart',
        type: TYPE_DATE,
    }),
    new Field({
        title: 'Data zakończenia',
        path: 'data.production.dateEnd',
        type: TYPE_DATE,
    }),
    new Field({
        title: 'Status',
        path: 'data.production.status',
        getter: record => {
            const status = statuses.find(s => s.value === Number(record.data.production.status))
            return status ? status.name : record.data.production.status
        }
    }),
    new Field({
        title: 'Współczynnik',
        path: 'data.factor',
        meta: {
            excel: {
                noteGetter: function(record) {
                    const factorsStack = this.defaultGetter(record, 'data.factorsStack') || []

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
    })
]