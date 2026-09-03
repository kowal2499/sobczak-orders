import moment from "moment";
import i18n from "@/../i18n";

/**
 * Zakresy dat wyliczane w chwili użycia. Kryterium trzyma sam token
 * (`{ preset: 'currentMonth' }`), więc widok zapisany we wrześniu
 * w październiku filtruje październik.
 */

const format = date => date.format('YYYY-MM-DD');

const range = (from, to) => ({ start: format(from), end: format(to) });

const definitions = {
    today: () => range(moment(), moment()),
    tomorrow: () => range(moment().add(1, 'day'), moment().add(1, 'day')),
    currentWeek: () => range(moment().startOf('isoWeek'), moment().endOf('isoWeek')),
    lastWeek: () => range(
        moment().subtract(1, 'week').startOf('isoWeek'),
        moment().subtract(1, 'week').endOf('isoWeek')
    ),
    nextWeek: () => range(
        moment().add(1, 'week').startOf('isoWeek'),
        moment().add(1, 'week').endOf('isoWeek')
    ),
    currentMonth: () => range(moment().startOf('month'), moment().endOf('month')),
    lastMonth: () => range(
        moment().subtract(1, 'month').startOf('month'),
        moment().subtract(1, 'month').endOf('month')
    ),
    nextMonth: () => range(
        moment().add(1, 'month').startOf('month'),
        moment().add(1, 'month').endOf('month')
    ),
    currentYear: () => range(moment().startOf('year'), moment().endOf('year')),
};

export const PRESET_KEYS = Object.keys(definitions);

const EMPTY_RANGE = { start: null, end: null };

/**
 * Czy wartość jest tokenem znanego presetu.
 *
 * @param {*} value
 * @returns {boolean}
 */
export function isPreset(value) {
    return Boolean(value && typeof value === 'object' && value.preset && definitions[value.preset]);
}

/**
 * Sprowadza wartość kryterium do konkretnych dat. Nieznany preset
 * (np. usunięty z aplikacji, a zapisany w starym widoku) znaczy „brak filtra".
 *
 * @param {*} value
 * @returns {{start: string|null, end: string|null}}
 */
export function resolveDateRange(value) {
    if (isPreset(value)) {
        return definitions[value.preset]();
    }

    if (!value || typeof value !== 'object' || value.preset) {
        return { ...EMPTY_RANGE };
    }

    return { start: value.start || null, end: value.end || null };
}

/**
 * @param {string} key
 * @returns {string}
 */
export function presetLabel(key) {
    return i18n.t(`listing.datePresets.${key}`);
}

/**
 * @returns {Array<{key: string, label: string}>}
 */
export function presetList() {
    return PRESET_KEYS.map(key => ({ key, label: presetLabel(key) }));
}
