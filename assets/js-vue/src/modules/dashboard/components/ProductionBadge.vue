<template>
    <base-badge :metric="metric">
        <template #title>
            {{ $t(metric.title) }}
        </template>

        <div v-if="canSeeProduction">
            <a href="#" @click.prevent="$emit('clicked', metric.id)">
                <div class="d-inline-block">
                {{ agreementLinesCount }}<small> / {{ agreementLinesFactors | roundFloat }}</small>
                </div>
            </a>
        </div>
        <div v-else>
            {{ agreementLinesCount }}
        </div>
    </base-badge>
</template>

<script>
import BaseBadge from "./BaseBadge";
export default {
    name: "ProductionBadge",
    components: {BaseBadge},
    props: {
        metric: {
            type: Object,
            required: true
        },
    },
    computed: {
        canSeeProduction() {
            return this.$user.can(this.$privilages.CAN_PRODUCTION);
        },
        agreementLinesCount() {
            if (false === Array.isArray(this.metric.value)) {
                return 0
            }
            if (!this.metric.value[0]) {
                return 0
            }
            return this.metric.value[0].count
        },
        agreementLinesFactors() {
            if (false === Array.isArray(this.metric.value)) {
                return 0
            }
            if (!this.metric.value[0]) {
                return 0
            }
            return this.metric.value[0].factors_summary
        }
    }
}
</script>

<style scoped>
    a:hover div {
        border-bottom: 1px solid #0056b3;
    }
</style>