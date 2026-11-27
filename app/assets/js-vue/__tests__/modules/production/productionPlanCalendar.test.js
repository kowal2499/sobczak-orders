import {
    planToEvents,
    applyEventToForm,
    loadToBackgroundEvents,
    defaultRange,
    isPlanEvent,
    changedSlugs,
    onlyDepartments,
    capacityByWeek,
    weekHeaderCell,
} from '@/modules/production/services/productionPlanCalendar'

const form = () => [
    { slug: 'dpt01', dateStart: '2026-12-01', dateEnd: '2026-12-03' },
    { slug: 'dpt02', dateStart: '2026-12-04', dateEnd: '2026-12-07' },
    { slug: 'dpt03', dateStart: '2026-12-08', dateEnd: null },
    { slug: 'dpt04', dateStart: null, dateEnd: '2026-12-10' },
]

describe('planToEvents', () => {
    it('creates an editable plan bar only for departments with both dates', () => {
        const events = planToEvents(form(), slug => `name-${slug}`)

        expect(events.map(e => e.id)).toEqual(['plan-dpt01', 'plan-dpt02'])
        expect(events[1]).toMatchObject({
            resourceId: 'dpt02',
            dateStart: '2026-12-04',
            dateEnd: '2026-12-07',
            eventType: 'plan',
            orderName: 'name-dpt02',
            orderStatus: 'planned',
        })
        expect(isPlanEvent(events[0])).toBe(true)
        expect(isPlanEvent({ id: 'prod-5' })).toBe(false)
    })
})

describe('applyEventToForm', () => {
    it('changes only the dragged department and leaves the input intact', () => {
        const input = form()
        const before = JSON.parse(JSON.stringify(input))

        const result = applyEventToForm(input, { resourceId: 'dpt02', dateStart: '2026-12-20', dateEnd: '2026-12-31' })

        expect(input).toEqual(before)
        expect(result[1]).toEqual({ slug: 'dpt02', dateStart: '2026-12-20', dateEnd: '2026-12-31' })
        expect(result[0]).toEqual(before[0])
    })
})

describe('loadToBackgroundEvents', () => {
    it('scales intensity against the department maximum', () => {
        const events = loadToBackgroundEvents({ dpt01: { '2026-12-01': 1, '2026-12-02': 4 } })

        expect(events).toHaveLength(2)
        expect(events[0]).toMatchObject({
            id: 'load-dpt01-2026-12-01',
            resourceId: 'dpt01',
            dateStart: '2026-12-01',
            dateEnd: '2026-12-01',
            eventType: 'background',
            color: 'rgba(var(--colorPrimaryRgb), 0.12)',
        })
        expect(events[1].color).toBe('rgba(var(--colorPrimaryRgb), 0.48)')
    })

    it('returns nothing for an empty load', () => {
        expect(loadToBackgroundEvents({})).toEqual([])
    })
})

describe('defaultRange', () => {
    it('widens the start to a past plan date and ends two weeks after the deadline', () => {
        const range = defaultRange(
            [{ slug: 'dpt01', dateStart: '2026-11-18', dateEnd: '2026-11-20' }],
            '2026-12-17',
            '2026-11-27'
        )

        expect(range).toEqual({ start: '2026-11-16', end: '2026-12-31' })
    })

    it('starts on the Monday of today when there are no dates', () => {
        const range = defaultRange([{ slug: 'dpt01', dateStart: null, dateEnd: null }], '2026-12-17', '2026-11-27')

        expect(range).toEqual({ start: '2026-11-23', end: '2026-12-31' })
    })

    it('extends the end past the deadline when a plan date is later', () => {
        const range = defaultRange([{ slug: 'dpt05', dateStart: '2026-12-20', dateEnd: '2027-01-05' }], '2026-12-17', '2026-11-27')

        expect(range.end).toBe('2027-01-19')
    })

    it('never ends before it starts when the deadline is already past', () => {
        const range = defaultRange([{ slug: 'dpt01', dateStart: null, dateEnd: null }], '2026-10-12', '2026-11-27')

        expect(range).toEqual({ start: '2026-11-23', end: '2026-12-11' })
    })

    it('keeps today in view when the whole plan lies in the past', () => {
        const range = defaultRange(
            [{ slug: 'dpt01', dateStart: '2026-09-17', dateEnd: '2026-09-24' }, { slug: 'dpt05', dateStart: '2026-10-06', dateEnd: '2026-10-08' }],
            '2026-10-12',
            '2026-11-27'
        )

        expect(range).toEqual({ start: '2026-09-14', end: '2026-12-11' })
    })

    it('ignores a mistyped date far outside a sane window', () => {
        const range = defaultRange(
            [{ slug: 'dpt01', dateStart: '2026-12-01', dateEnd: '2062-12-01' }, { slug: 'dpt02', dateStart: '0026-12-01', dateEnd: '2026-12-03' }],
            '2026-12-17',
            '2026-11-27'
        )

        expect(range).toEqual({ start: '2026-11-23', end: '2026-12-31' })
    })
})

describe('changedSlugs', () => {
    it('lists departments whose dates differ', () => {
        const next = applyEventToForm(form(), { resourceId: 'dpt02', dateStart: '2026-12-20', dateEnd: '2026-12-31' })

        expect(changedSlugs(form(), next)).toEqual(['dpt02'])
        expect(changedSlugs(form(), form())).toEqual([])
    })
})

describe('capacityByWeek', () => {
    it('collapses the per-day capacity rows into one entry per week', () => {
        const days = [
            { date: '2026-11-30', weekStart: '2026-11-30', capacity: 10, capacityBurned: 8.2 },
            { date: '2026-12-01', weekStart: '2026-11-30', capacity: 10, capacityBurned: 8.2 },
            { date: '2026-12-07', weekStart: '2026-12-07', capacity: 12, capacityBurned: 3 },
        ]

        expect(capacityByWeek(days)).toEqual({
            '2026-11-30': { capacity: 10, burned: 8.2 },
            '2026-12-07': { capacity: 12, burned: 3 },
        })
    })
})

describe('weekHeaderCell', () => {
    it('labels the week with its ISO number and the metric scale', () => {
        expect(weekHeaderCell('2026-11-23', { capacity: 10, burned: 9.6 })).toEqual({ label: 'T48 · 96%', percent: 96, variant: 'danger' })
    })

    it('shows only the week number when capacity is unknown', () => {
        expect(weekHeaderCell('2026-11-30', undefined)).toEqual({ label: 'T49', percent: null, variant: null })
    })
})

describe('onlyDepartments', () => {
    const items = [{ id: 'dpt01' }, { id: 'dpt02' }, { id: 'dpt03' }]

    it('keeps everything when nothing is selected', () => {
        expect(onlyDepartments(items, [], i => i.id)).toEqual(items)
    })

    it('keeps only the selected departments', () => {
        expect(onlyDepartments(items, ['dpt03', 'dpt01'], i => i.id)).toEqual([{ id: 'dpt01' }, { id: 'dpt03' }])
    })
})
