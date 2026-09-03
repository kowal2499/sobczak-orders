import { isPreset, presetLabel, resolveDateRange } from "@/services/dateRangePresets";

export const TYPE_TEXT = 'text'
export const TYPE_BOOLEAN = 'boolean'
export const TYPE_DATE_RANGE = 'dateRange'

export default class Criterion {

    /** @type {string} */
    id;

    /** @type {string} */
    #label;

    /** @type {string} */
    #type;

    /** @type {string} */
    #apiKey;

    /** @type {*} */
    #defaultValue;

    /** @type {function(*): string|null} */
    #formatter;

    /** @param {Object} data */
    constructor(data) {
        const { id, label, type, apiKey, defaultValue, formatter } = data || {}

        if (!id) {
            throw new Error('Criterion must have an id');
        }
        this.id = id;

        if (!label) {
            throw new Error('Criterion must have a label');
        }
        this.#label = label;

        this.#type = type || TYPE_TEXT;
        this.#apiKey = apiKey || id;
        this.#defaultValue = defaultValue;
        this.#formatter = formatter || null;
    }

    get label() {
        return this.#label;
    }

    get type() {
        return this.#type;
    }

    get apiKey() {
        return this.#apiKey;
    }

    /** Świeża kopia - wartości bywają obiektami, nie wolno współdzielić referencji. */
    get defaultValue() {
        return JSON.parse(JSON.stringify(this.#defaultValue === undefined ? null : this.#defaultValue));
    }

    /**
     * Czy wartość odpowiada „brak filtra". Steruje licznikiem aktywnych filtrów i chipami.
     *
     * @param {*} value
     */
    isEmpty(value) {
        if (value === null || value === undefined || value === '') {
            return true;
        }

        if (this.#type === TYPE_DATE_RANGE) {
            if (value.preset) {
                return !isPreset(value);
            }

            return !value.start && !value.end;
        }

        if (this.#type === TYPE_BOOLEAN) {
            return Boolean(value) === Boolean(this.#defaultValue);
        }

        return false;
    }

    /**
     * Wartość gotowa do wysłania na API - zakres relatywny rozwijany jest tutaj,
     * w widoku pozostaje sam token.
     *
     * @param {*} value
     * @returns {*}
     */
    resolveValue(value) {
        if (this.#type === TYPE_DATE_RANGE) {
            return resolveDateRange(value);
        }

        return value;
    }

    /**
     * Etykieta chipa dla ustawionej wartości.
     *
     * @param {*} value
     * @returns {string}
     */
    describe(value) {
        if (this.#formatter) {
            return this.#formatter(value);
        }

        if (this.#type === TYPE_DATE_RANGE) {
            if (isPreset(value)) {
                return `${this.#label}: ${presetLabel(value.preset)}`;
            }

            return `${this.#label}: ${value.start || '...'} - ${value.end || '...'}`;
        }

        if (this.#type === TYPE_BOOLEAN) {
            return this.#label;
        }

        return `${this.#label}: ${value}`;
    }
}
