import { capacityPercent, capacityVariant, weekNumberLabel } from '@/modules/schedule/services/capacityScale'

describe('capacityPercent', () => {
    it('rounds the burned share of capacity', () => {
        expect(capacityPercent(10, 9.64)).toBe(96)
        expect(capacityPercent(10, 10.5)).toBe(105)
    })

    it('is zero without capacity', () => {
        expect(capacityPercent(0, 3)).toBe(0)
    })
})

describe('capacityVariant', () => {
    it('follows the weekly capacity metric thresholds', () => {
        expect(capacityVariant(25)).toBe('success')
        expect(capacityVariant(26)).toBe('info')
        expect(capacityVariant(50)).toBe('info')
        expect(capacityVariant(55)).toBe('warning')
        expect(capacityVariant(75)).toBe('warning')
        expect(capacityVariant(96)).toBe('danger')
    })
})

describe('weekNumberLabel', () => {
    it('gives the ISO week number', () => {
        expect(weekNumberLabel('2026-11-02')).toBe('T45')
        expect(weekNumberLabel('2026-11-30')).toBe('T49')
    })
})
