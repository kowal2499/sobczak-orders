import { agreementLineSummary } from '@/modules/production/services/agreementLineSummary'

describe('agreementLineSummary', () => {
    it('reads the legacy line shape used by the orders list', () => {
        const line = {
            id: 4145,
            status: 10,
            confirmedDate: '2026-10-12',
            internalNumber: 'A',
            description: 'schody dębowe',
            Product: { name: 'STAIRS L' },
            Agreement: {
                orderNumber: '138068',
                createDate: '2026-09-01 10:15',
                user: { userFullName: 'Dan Dalsgaard' },
                Customer: { name: 'Trappefabrikken A/S', first_name: 'Jan', last_name: 'Nowak' },
            },
        }

        expect(agreementLineSummary(line)).toEqual({
            agreementLineId: 4145,
            orderNumber: '138068',
            internalNumber: 'A',
            customerName: 'Trappefabrikken A/S',
            productName: 'STAIRS L',
            status: 10,
            confirmedDate: '2026-10-12',
            createDate: '2026-09-01 10:15',
            issuedBy: 'Dan Dalsgaard',
            description: 'schody dębowe',
        })
    })

    it('reads the read-model line shape', () => {
        const line = {
            agreementLineId: 7,
            status: 5,
            confirmedDate: '2026-12-17',
            orderNumber: '830012',
            internalNumber: null,
            customerName: 'Timber Sp. z o.o.',
            productName: 'domówienie',
            agreementCreateDate: '2026-09-17',
            userName: 'Juliusz Kuncio',
            description: null,
            product: { description: 'opis produktu' },
        }

        expect(agreementLineSummary(line)).toEqual({
            agreementLineId: 7,
            orderNumber: '830012',
            internalNumber: null,
            customerName: 'Timber Sp. z o.o.',
            productName: 'domówienie',
            status: 5,
            confirmedDate: '2026-12-17',
            createDate: '2026-09-17',
            issuedBy: 'Juliusz Kuncio',
            description: 'opis produktu',
        })
    })
})
