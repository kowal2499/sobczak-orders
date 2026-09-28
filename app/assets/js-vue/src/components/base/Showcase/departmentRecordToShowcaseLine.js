import { getLocalDate } from '@/helpers'

// Rekord działowy (jedna produkcja linii, kształt z mapDetails) -> linia dla AgreementLineRmShowcaseItem.
// orderNumber jest już numerem wyświetlanym, więc internalNumber celowo zostaje pusty -
// inaczej kafelek dokleiłby sufiks drugi raz.
export function departmentRecordToShowcaseLine(record, isGhost = false) {
    const production = record.data?.production || {}

    return {
        agreementLineId: record.id,
        customerName: record.customerName,
        productName: record.productName,
        orderNumber: record.orderNumber,
        internalNumber: null,
        factor: record.factor,
        status: record.status,
        userName: record.userName,
        agreementCreateDate: record.agreementCreateDate ? getLocalDate(record.agreementCreateDate) : null,
        confirmedDate: record.confirmedDate ? getLocalDate(record.confirmedDate) : null,
        productions: [{
            departmentSlug: production.departmentSlug,
            status: production.status,
            dateStart: production.dateStart,
            dateEnd: production.dateEnd,
            factorRatio: {
                factor: record.data?.factor ?? null,
                factorsStack: record.data?.factorsStack || [],
            },
            isGhost,
        }],
    }
}
