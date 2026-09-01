import { fetchViews, saveViews } from "../repository/listingRepository";
import View from "./View";
import Column from "./Column";
import { v4 as uuid } from "uuid";
import i18n from "@/../i18n";

const SCHEMA_VERSION = 1;
const PERSIST_DELAY = 500;

export default class Listing {

    /** @type {string} */
    #id;

    /** @type {string} */
    #title;

    /** @type {Column[]} */
    supportedColumns;

    /** @type {Criterion[]} */
    supportedCriteria;

    /** @type {Object} bieżące wartości filtrów, klucz = id kryterium */
    criteriaValues;

    /** @type {string|null} */
    activeViewId;

    /** @type {View[]} */
    viewCollection;

    /** @type {boolean} */
    fetchingData = false;

    /** @type {number|null} */
    #persistTimer = null;

    /** @type {function(Error): void|null} */
    #errorHandler = null;

    /**
     * @param {string} id
     * @param {string} title
     * @param {Column[]} supportedColumns
     * @param {Criterion[]} supportedCriteria
     */
    constructor(id, title, supportedColumns, supportedCriteria) {
        this.#id = id || null;
        this.#title = title || null;
        this.supportedColumns = supportedColumns || [];
        this.supportedCriteria = supportedCriteria || [];

        this.activeViewId = null;
        this.viewCollection = [];
        this.criteriaValues = this.defaultCriteriaValues();

        this.fetchingData = false;
    }

    get id() {
        return this.#id;
    }

    get title() {
        return this.#title;
    }

    /** @returns {string[]} */
    get supportedColumnIds() {
        return this.supportedColumns.map(column => column.id);
    }

    /** @returns {View|null} */
    get activeView() {
        return this.viewCollection.find(view => view.id === this.activeViewId) || null;
    }

    /**
     * Kolumny wybrane w aktywnym widoku, w jego kolejności.
     *
     * @returns {Column[]}
     */
    get visibleColumns() {
        const view = this.activeView;
        if (!view) {
            return [];
        }

        return view.columnIds
            .map(id => this.supportedColumns.find(column => column.id === id))
            .filter(Boolean);
    }

    /** @param {string} id */
    isColumnVisible(id) {
        const view = this.activeView;
        return Boolean(view && view.columnIds.includes(id));
    }

    /** Liczba wiodących kolumn przyklejonych do lewej krawędzi. */
    get pinnedCount() {
        const view = this.activeView;

        return view ? view.pinnedCount : 0;
    }

    /** @param {string} id */
    isColumnPinned(id) {
        const view = this.activeView;

        return Boolean(view && view.isPinned(id));
    }

    /** @param {string} id */
    canPinColumn(id) {
        const view = this.activeView;

        return Boolean(view && (view.isPinned(id) || view.canPin(id)));
    }

    /** @param {string} id */
    togglePinnedColumn(id) {
        const view = this.activeView;
        if (!view) {
            return;
        }

        view.togglePin(id);
        this.persist();
    }

    /** @returns {Object} */
    defaultCriteriaValues() {
        return this.supportedCriteria.reduce((acc, criterion) => {
            acc[criterion.id] = criterion.defaultValue;
            return acc;
        }, {});
    }

    /**
     * Wartości zapisane w widoku, uzupełnione domyślnymi. Widok trzyma tylko to,
     * co odbiega od domyślnych, więc dołożenie nowego kryterium nie psuje starych zapisów.
     *
     * @returns {Object}
     */
    viewCriteriaValues() {
        const view = this.activeView;
        const stored = (view && view.criteria && !Array.isArray(view.criteria)) ? view.criteria : {};

        return { ...this.defaultCriteriaValues(), ...JSON.parse(JSON.stringify(stored)) };
    }

    /** @param {Object} values */
    setCriteriaValues(values) {
        this.criteriaValues = { ...this.defaultCriteriaValues(), ...JSON.parse(JSON.stringify(values || {})) };
    }

    /**
     * @param {string} id
     * @param {*} value
     */
    setCriterionValue(id, value) {
        this.criteriaValues = { ...this.criteriaValues, [id]: value };
    }

    /** @param {string} id */
    clearCriterion(id) {
        const criterion = this.supportedCriteria.find(item => item.id === id);
        if (!criterion) {
            return;
        }

        this.setCriterionValue(id, criterion.defaultValue);
    }

    /** Kryteria z ustawioną wartością - do chipów i licznika. */
    get activeCriteria() {
        return this.supportedCriteria
            .filter(criterion => !criterion.isEmpty(this.criteriaValues[criterion.id]))
            .map(criterion => ({ criterion, value: this.criteriaValues[criterion.id] }));
    }

    /** Czy bieżące filtry różnią się od zapisanych w widoku. */
    get isCriteriaDirty() {
        return JSON.stringify(this.criteriaValues) !== JSON.stringify(this.viewCriteriaValues());
    }

    /** Wraca do filtrów zapisanych w widoku. */
    restoreCriteria() {
        this.setCriteriaValues(this.viewCriteriaValues());
    }

    /** Czyści filtry do wartości domyślnych, bez zapisu. */
    clearCriteria() {
        this.criteriaValues = this.defaultCriteriaValues();
    }

    /** Utrwala bieżące filtry w aktywnym widoku. */
    saveCriteria() {
        const view = this.activeView;
        if (!view) {
            return;
        }

        const defaults = this.defaultCriteriaValues();
        view.criteria = this.supportedCriteria.reduce((acc, criterion) => {
            const value = this.criteriaValues[criterion.id];

            if (JSON.stringify(value) !== JSON.stringify(defaults[criterion.id])) {
                acc[criterion.id] = JSON.parse(JSON.stringify(value));
            }

            return acc;
        }, {});

        this.persist();
    }

    /** @param {function(Error): void} handler */
    onPersistError(handler) {
        this.#errorHandler = handler;
    }

    fetchViews() {
        this.fetchingData = true;
        return fetchViews(this.#id)
            .then(({ data: response }) => {
                const payload = (response && response.data) || null;
                const stored = (payload && Array.isArray(payload.viewCollection)) ? payload.viewCollection : [];

                this.viewCollection = stored.map(view => View.fromJSON(view));
                this.viewCollection.forEach(view => view.retainColumns(this.supportedColumnIds));
                this.activeViewId = (payload && payload.activeViewId) || null;

                if (!this.viewCollection.length) {
                    this.createView(i18n.t('listing.defaultViewTitle'), this.supportedColumnIds);
                } else if (!this.activeView) {
                    this.activeViewId = this.viewCollection[0].id;
                }
            })
            .catch(error => {
                // bez ustawień listing musi działać dalej - widok domyślny tylko lokalnie
                this.viewCollection = [this.buildView(i18n.t('listing.defaultViewTitle'), this.supportedColumnIds)];
                this.activeViewId = this.viewCollection[0].id;
                this.reportError(error);
            })
            .finally(() => {
                this.restoreCriteria();
                this.fetchingData = false;
            })
            ;
    }

    /**
     * @param {string} title
     * @param {string[]} columnIds
     * @returns {View}
     */
    buildView(title, columnIds) {
        return new View(uuid(), title, columnIds, {});
    }

    /**
     * @param {string} title
     * @param {string[]} columnIds
     * @returns {View}
     */
    createView(title, columnIds) {
        const view = this.buildView(title, columnIds);
        this.viewCollection = [...this.viewCollection, view];
        this.activeViewId = view.id;
        this.restoreCriteria();
        this.persist();

        return view;
    }

    /**
     * @param {string} id
     * @param {string} title
     */
    renameView(id, title) {
        const view = this.viewCollection.find(item => item.id === id);
        if (!view) {
            return;
        }
        view.rename(title);
        this.persist();
    }

    /**
     * @param {string} id
     * @returns {View|null}
     */
    duplicateView(id) {
        const source = this.viewCollection.find(item => item.id === id);
        if (!source) {
            return null;
        }

        const copy = source.clone(uuid(), i18n.t('listing.copyOfViewTitle', { title: source.title }));
        this.viewCollection = [...this.viewCollection, copy];
        this.activeViewId = copy.id;
        this.restoreCriteria();
        this.persist();

        return copy;
    }

    /**
     * Ostatniego widoku nie da się usunąć - listing zawsze potrzebuje jednego.
     *
     * @param {string} id
     * @returns {boolean}
     */
    removeView(id) {
        if (this.viewCollection.length <= 1) {
            return false;
        }

        const index = this.viewCollection.findIndex(view => view.id === id);
        if (index === -1) {
            return false;
        }

        this.viewCollection = this.viewCollection.filter(view => view.id !== id);

        if (this.activeViewId === id) {
            this.activeViewId = this.viewCollection[Math.max(0, index - 1)].id;
            this.restoreCriteria();
        }
        this.persist();

        return true;
    }

    /**
     * @param {string} id
     * @param {number} offset dodatni w prawo, ujemny w lewo
     */
    moveView(id, offset) {
        const from = this.viewCollection.findIndex(view => view.id === id);
        if (from === -1) {
            return;
        }

        const to = from + offset;
        if (to < 0 || to >= this.viewCollection.length) {
            return;
        }

        const views = [...this.viewCollection];
        views.splice(to, 0, ...views.splice(from, 1));
        this.viewCollection = views;
        this.persist();
    }

    /** @param {string} id */
    toggleColumn(id) {
        const view = this.activeView;
        if (!view || !this.supportedColumnIds.includes(id)) {
            return;
        }

        view.toggleColumn(id);
        this.persist();
    }

    /** @param {string[]} ids */
    setColumnOrder(ids) {
        const view = this.activeView;
        if (!view) {
            return;
        }

        view.setColumnIds(ids.filter(id => this.supportedColumnIds.includes(id)));
        this.persist();
    }

    /**
     * @param {string} id
     * @param {number} offset
     */
    moveColumn(id, offset) {
        const view = this.activeView;
        if (!view) {
            return;
        }

        view.moveColumn(id, offset);
        this.persist();
    }

    /** @param {string} id */
    setActiveView(id) {
        if (!this.viewCollection.some(view => view.id === id)) {
            return;
        }

        this.activeViewId = id;
        this.restoreCriteria();
        this.persist();
    }

    /** Zapis jest zbiorczy - seria kliknięć w checkboxy to jeden PUT. */
    persist() {
        if (this.#persistTimer) {
            clearTimeout(this.#persistTimer);
        }

        this.#persistTimer = setTimeout(() => {
            this.#persistTimer = null;
            this.flush();
        }, PERSIST_DELAY);
    }

    flush() {
        return saveViews(this.#id, this.toJSON())
            .catch(error => this.reportError(error));
    }

    /** @param {Error} error */
    reportError(error) {
        if (this.#errorHandler) {
            this.#errorHandler(error);
        }
    }

    toJSON() {
        return {
            version: SCHEMA_VERSION,
            activeViewId: this.activeViewId,
            viewCollection: this.viewCollection.map(view => view.toJSON())
        }
    }
}
