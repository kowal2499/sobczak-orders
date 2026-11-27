import { buildColumns, columnOf, clipToRange, clampResizeOffset, groupWeeks, groupMonths } from '@/modules/schedule/components/ResourceCalendar/utils/timeline'

describe('buildColumns', () => {
    it('lists every day of the range inclusively', () => {
        const columns = buildColumns('2026-11-30', '2026-12-02')

        expect(columns).toHaveLength(3)
        expect(columns[2].format('YYYY-MM-DD')).toBe('2026-12-02')
    })
})

describe('columnOf', () => {
    it('counts days from the range start, negative before it', () => {
        expect(columnOf('2026-11-30', '2026-12-02')).toBe(2)
        expect(columnOf('2026-11-30', '2026-11-28')).toBe(-2)
    })
})

describe('clipToRange', () => {
    it('clips an event starting in the previous month to the first column', () => {
        expect(clipToRange('2026-10-28', '2026-11-03', '2026-11-01', '2026-11-30')).toEqual({ startCol: 0, endCol: 2 })
    })

    it('keeps an event spanning the year boundary whole inside a wider range', () => {
        expect(clipToRange('2026-12-29', '2027-01-04', '2026-12-28', '2027-01-31')).toEqual({ startCol: 1, endCol: 7 })
    })

    it('returns null for an event outside the range', () => {
        expect(clipToRange('2026-12-01', '2026-12-03', '2026-11-01', '2026-11-30')).toBeNull()
    })
})

describe('clampResizeOffset', () => {
    it('stops the start edge on the end day', () => {
        expect(clampResizeOffset('start', '2026-12-01', '2026-12-03', 5)).toBe(2)
    })

    it('lets the start edge move freely to the left', () => {
        expect(clampResizeOffset('start', '2026-12-01', '2026-12-03', -4)).toBe(-4)
    })

    it('stops the end edge on the start day', () => {
        expect(clampResizeOffset('end', '2026-12-01', '2026-12-03', -5)).toBe(-2)
    })
})

describe('groupWeeks', () => {
    it('splits columns into ISO weeks, the first one possibly partial', () => {
        const weeks = groupWeeks(buildColumns('2026-11-27', '2026-12-08'))

        expect(weeks).toEqual([
            { weekStart: '2026-11-23', startCol: 0, span: 3 },
            { weekStart: '2026-11-30', startCol: 3, span: 7 },
            { weekStart: '2026-12-07', startCol: 10, span: 2 },
        ])
    })
})

describe('groupMonths', () => {
    it('splits columns into calendar months', () => {
        const months = groupMonths(buildColumns('2026-09-28', '2026-11-02'))

        expect(months).toEqual([
            { year: 2026, month: 8, startCol: 0, span: 3 },
            { year: 2026, month: 9, startCol: 3, span: 31 },
            { year: 2026, month: 10, startCol: 34, span: 2 },
        ])
    })
})
