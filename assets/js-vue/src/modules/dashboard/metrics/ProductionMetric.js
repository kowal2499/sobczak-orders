import Metric from "./DashboardMetric";

export default class ProductionMetric extends Metric
{
    constructor(id, title, value, className, groupId, detailsPromise = null) {
        super(id, title, value, className, groupId, detailsPromise);
    }

    getValue() {
        if (false === Array.isArray(this.value)) {
            return 0
        }
        if (!this.value[0]) {
            return 0
        }
        const summary = Math.floor((this.value[0].factors_summary || 0) * 100) / 100;
        return `${this.value[0].count} / ${summary}`
    }
}