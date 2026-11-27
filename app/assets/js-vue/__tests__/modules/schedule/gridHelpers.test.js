import { assignLanes, eventPositionStyle, eventColors } from '@/modules/schedule/components/ResourceCalendar/utils/gridHelpers'

describe('assignLanes', () => {
    it('puts overlapping events on separate lanes and reuses a freed lane', () => {
        const events = [{ startCol: 0, endCol: 3 }, { startCol: 2, endCol: 4 }, { startCol: 4, endCol: 5 }]

        expect(assignLanes(events).map(e => e.lane)).toEqual([0, 1, 0])
    })

    it('keeps background events out of the lanes taken by bars', () => {
        const events = [
            { startCol: 3, endCol: 3, eventType: 'background' },
            { startCol: 3, endCol: 6 },
            { startCol: 4, endCol: 4, eventType: 'background' },
        ]

        expect(assignLanes(events).map(e => e.lane)).toEqual([0, 0, 0])
    })
})

describe('eventPositionStyle', () => {
    it('positions by column index and lane', () => {
        expect(eventPositionStyle({ startCol: 2, endCol: 4, lane: 1 })).toEqual({ left: '260px', width: '118px', top: '30px' })
    })
})

describe('eventColors', () => {
    it('renders a planned bar in the primary colour with white text', () => {
        expect(eventColors({ orderStatus: 'planned' })).toEqual({
            background: 'var(--colorPrimary)',
            borderColor: 'var(--colorPrimary)',
            color: '#ffffff',
        })
    })
})
