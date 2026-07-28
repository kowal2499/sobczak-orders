import { orderDisplayNumber } from '@/helpers'

export default {
    methods: {
        mapDetails(data) {
            const agreementLinesMap = data?.reduce((acc, item) => {
                if (!acc.has(item.agreementLine.id)) {
                    acc.set(item.agreementLine.id, {
                        ...item.agreementLine,
                        ...item.agreement,
                        orderNumber: orderDisplayNumber(item.agreement.orderNumber, item.agreementLine.internalNumber),
                        factor: item.agreementLine.factor,
                        customerName: item.customer.name,
                        completedAt: item.completedAt,
                        involved_dpt01: {factor: null, factorsStack: [], production: null},
                        involved_dpt02: {factor: null, factorsStack: [], production: null},
                        involved_dpt03: {factor: null, factorsStack: [], production: null},
                        involved_dpt04: {factor: null, factorsStack: [], production: null},
                        involved_dpt05: {factor: null, factorsStack: [], production: null},
                        involved_dpt06: {factor: null, factorsStack: [], production: null},
                    })
                }

                const lineData = acc.get(item.agreementLine.id)
                // onTime/inRange !== false: brak flagi (starsze mierniki) traktujemy jak "w terminie"/"w zakresie".
                // Poza oknem zerujemy współczynnik u źródła, żeby sumy w tabeli/agregacji się zgadzały;
                // factorsStack zostaje (popover pokazuje, co przepadło).
                // Poza zakresem raportu backend nie przysyła współczynnika (factors === null) — komórka
                // pokazuje "–", a w popoverze samo okno produkcji.
                const isOnTime = item.onTime !== false
                const isInRange = item.inRange !== false
                lineData[`involved_${item.departmentSlug}`] = {
                    factor: null,
                    factorsStack: [],
                    ...(isInRange ? item.factors : {}),
                    ...(isInRange && !isOnTime ? { factor: 0 } : {}),
                    onTime: isOnTime,
                    inRange: isInRange,
                    production: {
                        status: item.status,
                        dateStart: item.dateStart,
                        dateEnd: item.dateEnd,
                        completedAt: item.completedAt,
                        departmentSlug: item.departmentSlug,
                    }
                }

                return acc
            }, new Map())

            return [...agreementLinesMap.values()]
        }
    }
}