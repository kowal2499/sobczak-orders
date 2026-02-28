import { saveSchedule } from '@/modules/configuration/repository/workRepository'

/**
 * Przetwarza eventy do zapisania zgodnie z logiką biznesową:
 *
 * 1. Jeśli nowy event ma typ "holiday" i przy tym dniu jest już event gdzie "identifier" wynosi null
 *    -> pomijaj ten event przy zapisie (to domyślny dzień wolny, nie nadpisujemy)
 *
 * 2. Jeśli przy dniu nie ma żadnego eventu, a nowy event ma typ "working"
 *    -> pomijaj (dzień już jest roboczy)
 *
 * 3. Jeśli przy danym dniu jest event o typie "holiday" z identifier różnym od null
 *    i nowy event ma typ "working"
 *    -> nadpisz istniejący event eventem 'working'
 *
 * 4. Jeśli przy danym dniu jest event z identifier === null (domyślny dzień wolny)
 *    i nowy event ma typ "working"
 *    -> zapisz event 'working' (oznacz domyślny dzień wolny jako roboczy)
 *
 * @param {Array} newEvents - nowe eventy do zapisania [{date, dayType, description}]
 * @param {Array} existingEvents - istniejące eventy z kalendarza [{identifier, dayType, start, ...}]
 * @returns {Promise}
 */
export function processScheduleChanges(newEvents, existingEvents) {
    const eventsToSave = []

    for (const newEvent of newEvents) {
        const existingEvent = findExistingEventByDate(newEvent.date, existingEvents)

        if (newEvent.dayType === 'holiday') {
            // Przypadek 1: Pomijaj, jeśli istnieje event bez identyfikatora (weekend)
            if (existingEvent && existingEvent.identifier === null) {
                continue
            }
            eventsToSave.push(newEvent)
        } else if (newEvent.dayType === 'working') {
            // Przypadek 2: Pomijaj, jeśli nie ma żadnego eventu (dzień już jest roboczy)
            if (!existingEvent) {
                continue
            }

            if (existingEvent.identifier !== null) {
                // Przypadek 3a: Istniejący event to 'holiday' z identyfikatorem - nadpisz go eventem 'working'
                if (existingEvent.dayType === 'holiday') {
                    eventsToSave.push(newEvent)
                }
                // Przypadek 3b: Istniejący event to 'working' z identyfikatorem - pomijaj (już jest roboczy)
                // nie robimy nic
            } else {
                // Przypadek 4: Weekend (identifier === null) - dodaj event 'working'
                eventsToSave.push(newEvent)
            }
        }
    }

    if (eventsToSave.length > 0) {
        return saveSchedule(eventsToSave)
    }

    return Promise.resolve()
}

/**
 * Znajduje istniejący event po dacie
 * @param {string} dateStr - data w formacie YYYY-MM-DD
 * @param {Array} existingEvents - istniejące eventy
 * @returns {Object|null}
 */
function findExistingEventByDate(dateStr, existingEvents) {
    return existingEvents.find(event => {
        const eventDate = formatDateLocal(event.start)
        return eventDate === dateStr
    }) || null
}

/**
 * Formatuje datę do YYYY-MM-DD w lokalnej strefie czasowej
 * @param {Date} date
 * @returns {string}
 */
function formatDateLocal(date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}
