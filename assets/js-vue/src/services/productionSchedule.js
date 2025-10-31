// Service for computing and validating production schedule dates
// It centralizes business rules so components stay lean and readable.

import { getLocalDate, DPT_GLUEING } from "../helpers";

/**
 * Subtract N working days (Mon-Fri) from a date and return a new Date instance.
 * Original date is not mutated.
 * @param {Date} date
 * @param {number} days
 * @returns {Date}
 */
export function subtractWorkingDays(date, days) {
    let d = new Date(date.getTime());
    let remaining = days;
    while (remaining > 0) {
        d.setDate(d.getDate() - 1);
        const wd = d.getDay();
        if (wd !== 0 && wd !== 6) {
            remaining--;
        }
    }
    return d;
}

/**
 * Compute default start/end dates for the department.
 * - start: today (local date string)
 * - end: for dpt01 -> confirmedDate - 5 working days; for others -> confirmedDate
 * @param {string} dptSlug
 * @param {Date} confirmedDate
 * @param {Date} [today]
 * @returns {{ start: string, end: string }} ISO local date strings (yyyy-mm-dd)
 */
export function computeDefaultDatesForDepartment(dptSlug, confirmedDate, today = new Date()) {
    const start = getLocalDate(today);
    const endDate = dptSlug === DPT_GLUEING
        ? subtractWorkingDays(confirmedDate, 5)
        : new Date(confirmedDate.getTime());
    return {
        start,
        end: getLocalDate(endDate)
    };
}
