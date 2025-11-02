
import { getLocalDate, DPT_GLUEING } from "@/helpers";

/**
 * @param {Date} date
 * @param {direction: 'before'|'after'} options
 * @returns {Date}
 */
export function shiftToWorkingDay(date, { direction }) {
    const d = new Date(date.getTime());
    if (false === [0, 6].includes(d.getDate())) {
        return d;
    }
    const delta = direction === 'before' ? -1 : 1;
    while (true) {
        d.setDate(d.getDate() + delta);
        const wd = d.getDay();
        if (wd !== 0 && wd !== 6) {
            break;
        }
    }
    return d;
}

/**
 * @param {Date} date
 * @param {{ count: number, direction: 'before'|'after' }} options
 * @returns {Date}
 */
function shiftByDays(date, { count, direction }) {
    const d = new Date(date.getTime());
    const delta = direction === 'before' ? -count : count;
    d.setDate(d.getDate() + delta);
    return d;
}

/**
 * @param {Date} date
 * @param {{ day: 'niedziela'|'poniedziałek'|'wtorek'|'środa'|'czwartek'|'piątek'|'sobota', direction: 'before'|'after' }} options
 * @returns {Date}
 */
function shiftByWeekday(date, { day, direction }) {
    const weekdays = ['niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'];
    const targetDayIndex = weekdays.indexOf(day.toLowerCase());
    if (targetDayIndex === -1) throw new Error(`Invalid weekday name: ${day}`);

    const currentDayIndex = date.getDay();
    if (currentDayIndex === targetDayIndex) {
        return new Date(date.getTime());
    }
    const deltaDays = (direction === 'before')
        ? (currentDayIndex - targetDayIndex + 7) % 7
        : (targetDayIndex - currentDayIndex + 7) % 7

    const d = new Date(date.getTime());
    d.setDate(d.getDate() + (direction === 'before' ? -deltaDays : deltaDays));
    return d;
}

/**
 * @param {Object} config
 * @param {Date} deadlineDate
 * @returns {Object}
 */
function applyStrategy(config, deadlineDate) {
    if (!config || typeof config !== 'object') {
        throw new Error('applyStrategy: config must be an object');
    }
    if (!deadlineDate) {
        throw new Error('applyStrategy: deadlineDate is required');
    }

    const baseDate = (deadlineDate instanceof Date)
        ? new Date(deadlineDate.getTime())
        : new Date(deadlineDate);

    if (isNaN(baseDate.getTime())) {
        throw new Error('applyStrategy: deadlineDate is invalid');
    }

    const methods = {
        shiftByDays,
        shiftByWeekday,
        shiftToWorkingDay,
    };

    const pending = new Set(Object.keys(config));

    let progressed = true;
    while (pending.size > 0 && progressed) {
        progressed = false;
        for (const key of Array.from(pending)) {
            const node = config[key] || {};
            const dep = node.dependentOn;

            let currentBase;
            if (dep === 'deadlineDate') {
                currentBase = baseDate;
            } else if (dep === 'todayDate') {
                currentBase = new Date();
            } else if (dep && config[dep] && config[dep].value instanceof Date) {
                currentBase = config[dep].value;
            } else {
                // dependency not ready yet
                continue;
            }

            // Apply steps sequentially
            let current = new Date(currentBase.getTime());
            const steps = Array.isArray(node.steps) ? node.steps : [];
            for (const step of steps) {
                const methodName = step && step.method;
                const params = (step && step.params) || {};
                const fn = methods[methodName];
                if (typeof fn !== 'function') {
                    throw new Error(`applyStrategy: unknown method: ${methodName}`);
                }
                current = fn(current, params);
            }

            // Save value and mark as resolved
            config[key].value = current;
            pending.delete(key);
            progressed = true;
        }
    }

    if (pending.size > 0) {
        throw new Error(`applyStrategy: unresolved dependencies: ${Array.from(pending).join(', ')}`);
    }

    return config;
}

export {
    shiftByDays,
    shiftByWeekday,
    applyStrategy,
}