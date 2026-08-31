export default class Column {

    /** @type {string} */
    id;

    /** @type {string} */
    #label;

    /** @type {string|function(Object): *|null} */
    #apiPath;

    /** @type {string|null} */
    #apiSortKey;

    /** @type {Object|string|null} */
    #displayComponent;

    /** @type {Array<{label: string, sortKey: string|null}>|null} */
    #headerItems;

    /** @type {function(*, Object): string|null} */
    #formatter;

    /** @type {string|null} */
    #thClass;

    /** @type {string|null} */
    #tdClass;

    /** @param {Object} data */
    constructor(data) {
        const { id, apiPath, apiSortKey, label, displayComponent, headerItems, formatter, thClass, tdClass } = data || {}

        if (!id) {
            throw new Error('Column must have an id');
        }
        this.id = id;

        if (!label) {
            throw new Error('Column must have a label');
        }
        this.#label = label;

        this.#apiPath = apiPath || null;
        this.#apiSortKey = apiSortKey || null;
        this.#displayComponent = displayComponent || null;
        this.#headerItems = (headerItems && headerItems.length) ? headerItems : null;
        this.#formatter = formatter || null;
        this.#thClass = thClass || null;
        this.#tdClass = tdClass || null;
    }

    get label() {
        return this.#label;
    }

    get apiPath() {
        return this.#apiPath;
    }

    get apiSortKey() {
        return this.#apiSortKey;
    }

    get sortable() {
        return Boolean(this.#apiSortKey);
    }

    get displayComponent() {
        return this.#displayComponent;
    }

    get thClass() {
        return this.#thClass;
    }

    get tdClass() {
        return this.#tdClass;
    }

    /**
     * Linie nagłówka. Kolumny działów mają ich kilka, każda z własnym kluczem sortowania.
     *
     * @returns {Array<{label: string, sortKey: string|null}>}
     */
    get headerItems() {
        return this.#headerItems || [{ label: this.#label, sortKey: this.#apiSortKey }];
    }

    /**
     * @param {Object} record
     * @returns {*}
     */
    resolveValue(record) {
        if (!this.#apiPath || !record) {
            return null;
        }

        if (typeof this.#apiPath === 'function') {
            return this.#apiPath(record);
        }

        return this.#apiPath
            .split('.')
            .reduce((value, key) => (value === null || value === undefined) ? null : value[key], record);
    }

    /**
     * Wartość gotowa do wyświetlenia, gdy kolumna nie ma własnego komponentu.
     *
     * @param {Object} record
     * @returns {*}
     */
    format(record) {
        const value = this.resolveValue(record);

        return this.#formatter ? this.#formatter(value, record) : value;
    }
}
