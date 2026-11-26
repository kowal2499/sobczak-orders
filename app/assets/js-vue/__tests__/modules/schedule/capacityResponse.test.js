import { expandCapacityDays, withExpandedCapacityDays } from '@/modules/schedule/repository/capacityResponse'

describe('expandCapacityDays', () => {
    const payload = {
        days: [
            { date: '2026-02-05', weekStart: '2026-02-02', capacity: 7, capacityBurned: 1 },
            { date: '2026-02-06', weekStart: '2026-02-02', capacity: 7, capacityBurned: 1 },
            { date: '2026-02-09', weekStart: '2026-02-09', capacity: 7, capacityBurned: 0 },
        ],
        agreementLinesByWeek: {
            '2026-02-02': [{ agreementLineId: 1 }],
        },
    }

    it('gives every day the lines of its week, sharing one array per week', () => {
        const days = expandCapacityDays(payload)

        expect(days.map(d => d.agreementLines.length)).toEqual([1, 1, 0])
        expect(days[0].agreementLines).toBe(days[1].agreementLines)
        expect(days[0]).toMatchObject({ date: '2026-02-05', capacity: 7, capacityBurned: 1 })
    })

    it('treats an empty week map sent as an array like an empty object', () => {
        expect(expandCapacityDays({ ...payload, agreementLinesByWeek: [] })[0].agreementLines).toEqual([])
    })

    it('returns no days for an empty payload', () => {
        expect(expandCapacityDays(null)).toEqual([])
    })

    it('replaces the response data and keeps the rest of the response', async () => {
        const response = await withExpandedCapacityDays(Promise.resolve({ status: 200, data: payload }))

        expect(response.status).toBe(200)
        expect(response.data).toHaveLength(3)
    })
})
