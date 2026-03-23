import { processScheduleChanges } from '@/modules/schedule/services/WorkScheduleService'
import { saveSchedule } from '@/modules/configuration/repository/workRepository'

// Mock modułu workRepository
jest.mock('../../src/modules/configuration/repository/workRepository', () => ({
    saveSchedule: jest.fn(() => Promise.resolve())
}))

describe('WorkScheduleService', () => {
    beforeEach(() => {
        jest.clearAllMocks()
    })

    // Helper do tworzenia istniejącego eventu
    const createExistingEvent = (date, identifier, dayType = 'holiday') => ({
        identifier,
        dayType,
        title: 'Test event',
        start: new Date(`${date}T12:00:00`),
        end: new Date(`${date}T12:00:00`),
        allDay: true,
        display: 'background'
    })

    // Helper do tworzenia nowego eventu
    const createNewEvent = (date, dayType, description = '') => ({
        date,
        dayType,
        description
    })

    describe('processScheduleChanges', () => {
        describe('nowy event typu "holiday"', () => {
            it('powinien zapisać event gdy nie ma istniejącego eventu', async () => {
                const newEvents = [createNewEvent('2026-02-15', 'holiday', 'Święto')]
                const existingEvents = []

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-15', dayType: 'holiday', description: 'Święto' }
                ])
            })

            it('powinien pominąć event gdy istnieje domyślny dzień wolny (identifier === null)', async () => {
                const newEvents = [createNewEvent('2026-02-14', 'holiday', 'Święto')]
                const existingEvents = [
                    createExistingEvent('2026-02-14', null, 'holiday')
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).not.toHaveBeenCalled()
            })

            it('powinien zapisać event gdy istnieje event z identyfikatorem (nadpisanie)', async () => {
                const newEvents = [createNewEvent('2026-02-15', 'holiday', 'Nowe święto')]
                const existingEvents = [
                    createExistingEvent('2026-02-15', 123, 'working')
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-15', dayType: 'holiday', description: 'Nowe święto' }
                ])
            })
        })

        describe('nowy event typu "working"', () => {
            it('powinien pominąć event gdy nie ma istniejącego eventu (dzień już jest roboczy)', async () => {
                const newEvents = [createNewEvent('2026-02-16', 'working')]
                const existingEvents = []

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).not.toHaveBeenCalled()
            })

            it('powinien zapisać event gdy istnieje domyślny dzień wolny (identifier === null)', async () => {
                const newEvents = [createNewEvent('2026-02-14', 'working', 'Dzień roboczy')]
                const existingEvents = [
                    createExistingEvent('2026-02-14', null, 'holiday')
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-14', dayType: 'working', description: 'Dzień roboczy' }
                ])
            })

            it('powinien zapisać event gdy istnieje holiday z identyfikatorem (nadpisanie)', async () => {
                const newEvents = [createNewEvent('2026-02-15', 'working')]
                const existingEvents = [
                    createExistingEvent('2026-02-15', 123, 'holiday')
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-15', dayType: 'working', description: '' }
                ])
            })

            it('powinien pominąć event gdy istnieje working z identyfikatorem (już roboczy)', async () => {
                const newEvents = [createNewEvent('2026-02-15', 'working')]
                const existingEvents = [
                    createExistingEvent('2026-02-15', 123, 'working')
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).not.toHaveBeenCalled()
            })
        })

        describe('wiele eventów naraz', () => {
            it('powinien prawidłowo przetworzyć mieszane eventy', async () => {
                const newEvents = [
                    createNewEvent('2026-02-14', 'working'),  // domyślny wolny -> zapisz
                    createNewEvent('2026-02-15', 'holiday', 'Święto'),  // brak -> zapisz
                    createNewEvent('2026-02-16', 'working'),  // brak -> pomiń
                    createNewEvent('2026-02-17', 'holiday'),  // domyślny wolny -> pomiń
                ]
                const existingEvents = [
                    createExistingEvent('2026-02-14', null, 'holiday'),
                    createExistingEvent('2026-02-17', null, 'holiday'),
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-14', dayType: 'working', description: '' },
                    { date: '2026-02-15', dayType: 'holiday', description: 'Święto' },
                ])
            })

            it('powinien zwrócić Promise.resolve gdy nie ma eventów do zapisania', async () => {
                const newEvents = [
                    createNewEvent('2026-02-14', 'holiday'),  // domyślny wolny -> pomiń
                    createNewEvent('2026-02-16', 'working'),  // brak -> pomiń
                ]
                const existingEvents = [
                    createExistingEvent('2026-02-14', null, 'holiday'),
                ]

                const result = await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).not.toHaveBeenCalled()
                expect(result).toBeUndefined()
            })
        })

        describe('scenariusz: weekend -> working -> holiday -> working', () => {
            it('krok 1: weekend -> working (zapisz working)', async () => {
                const newEvents = [createNewEvent('2026-02-14', 'working')]
                const existingEvents = [
                    createExistingEvent('2026-02-14', null, 'holiday')  // weekend
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-14', dayType: 'working', description: '' }
                ])
            })

            it('krok 2: working -> holiday (zapisz holiday)', async () => {
                const newEvents = [createNewEvent('2026-02-14', 'holiday', 'Święto')]
                const existingEvents = [
                    createExistingEvent('2026-02-14', 123, 'working')  // ustawiony working
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-14', dayType: 'holiday', description: 'Święto' }
                ])
            })

            it('krok 3: holiday -> working (zapisz working, nadpisując holiday)', async () => {
                const newEvents = [createNewEvent('2026-02-14', 'working')]
                const existingEvents = [
                    createExistingEvent('2026-02-14', 123, 'holiday')  // ustawione holiday
                ]

                await processScheduleChanges(newEvents, existingEvents)

                expect(saveSchedule).toHaveBeenCalledWith([
                    { date: '2026-02-14', dayType: 'working', description: '' }
                ])
            })
        })
    })
})
