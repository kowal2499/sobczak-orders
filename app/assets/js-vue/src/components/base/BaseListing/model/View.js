export default class View {

    /** @type {string} */
    id;

    /** @type {string} */
    title;

    /** @type {string[]} */
    columnIds;

    /** @type {Object} */
    criteria

    /**
     * Liczba wiodących kolumn przyklejonych do lewej krawędzi. Przypięcie jest zawsze
     * ciągłym prefiksem, więc wystarczy licznik - nie ma stanu, w którym przypięta
     * kolumna ma przed sobą nieprzypiętą.
     *
     * @type {number}
     */
    pinnedCount

    /**
     * @param {string} id
     * @param {string} title
     * @param {string[]} columnIds
     * @param {Object} criteria
     * @param {number} pinnedCount
     */
    constructor(id, title, columnIds, criteria, pinnedCount) {
        this.id = id || null;
        this.title = title || null;
        this.columnIds = Array.isArray(columnIds) ? [...columnIds] : [];
        this.criteria = criteria || {};
        this.pinnedCount = 0;
        this.setPinnedCount(pinnedCount || 0);
    }

    /** @param {string} id */
    isPinned(id) {
        const index = this.columnIds.indexOf(id);

        return index !== -1 && index < this.pinnedCount;
    }

    /**
     * Przypiąć można tylko kolumnę tuż za ostatnią przypiętą - inaczej powstałaby dziura.
     *
     * @param {string} id
     */
    canPin(id) {
        return this.columnIds.indexOf(id) === this.pinnedCount;
    }

    /**
     * Odpięcie kolumny odpina też wszystkie za nią, żeby prefiks pozostał ciągły.
     *
     * @param {string} id
     */
    togglePin(id) {
        const index = this.columnIds.indexOf(id);
        if (index === -1) {
            return;
        }

        if (this.isPinned(id)) {
            this.setPinnedCount(index);
        } else if (this.canPin(id)) {
            this.setPinnedCount(index + 1);
        }
    }

    /** @param {number} count */
    setPinnedCount(count) {
        this.pinnedCount = Math.max(0, Math.min(Number(count) || 0, this.columnIds.length));
    }

    /** @param {string} title */
    rename(title) {
        const value = String(title || '').trim();
        if (!value) {
            throw new Error('View title must not be empty');
        }
        this.title = value;
    }

    /** Włączona kolumna trafia na koniec - kolejnością steruje się osobno. */
    toggleColumn(id) {
        if (!this.columnIds.includes(id)) {
            this.columnIds = [...this.columnIds, id];
        } else {
            const index = this.columnIds.indexOf(id);
            this.columnIds = this.columnIds.filter(colId => colId !== id);

            // wyłączenie przypiętej kolumny odpina ją razem z tym, co za nią
            if (index < this.pinnedCount) {
                this.setPinnedCount(index);
            }
        }
    }

    /** @param {string[]} ids */
    setColumnIds(ids) {
        this.columnIds = [...ids];
        this.setPinnedCount(this.pinnedCount);
    }

    /**
     * @param {string} id
     * @param {number} offset dodatni w prawo, ujemny w lewo
     */
    moveColumn(id, offset) {
        const from = this.columnIds.indexOf(id);
        if (from === -1) {
            return;
        }

        const to = from + offset;
        if (to < 0 || to >= this.columnIds.length) {
            return;
        }

        const ids = [...this.columnIds];
        ids.splice(to, 0, ...ids.splice(from, 1));
        this.columnIds = ids;
    }

    /**
     * Usuwa kolumny, których listing już nie obsługuje (np. po odebraniu grantu).
     *
     * @param {string[]} allowedIds
     */
    retainColumns(allowedIds) {
        const removedBeforePin = this.columnIds
            .slice(0, this.pinnedCount)
            .filter(id => !allowedIds.includes(id))
            .length;

        this.columnIds = this.columnIds.filter(id => allowedIds.includes(id));
        this.setPinnedCount(this.pinnedCount - removedBeforePin);
    }

    /**
     * @param {string} id
     * @param {string} title
     * @returns {View}
     */
    clone(id, title) {
        return new View(id, title, this.columnIds, JSON.parse(JSON.stringify(this.criteria)), this.pinnedCount);
    }

    toJSON() {
        return {
            id: this.id,
            title: this.title,
            columnIds: [...this.columnIds],
            criteria: JSON.parse(JSON.stringify(this.criteria)),
            pinnedCount: this.pinnedCount
        }
    }

    static fromJSON(data) {
        return new View(data.id, data.title, data.columnIds, data.criteria, data.pinnedCount);
    }
}
