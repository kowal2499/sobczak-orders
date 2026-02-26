<script>
import ShowcaseBadge from './ShowcaseBadge.vue';
import { agreementStatusesMap } from '@/helpers'

export default {
    name: "ShowcaseAgreementLineStatus",
    props: {
        value: [String, Number],
        backgroundClass: {
            type: String,
            default: 'color-background-white-100'
        }
    },
    components: {
        ShowcaseBadge
    },
    computed: {
        statusName() {
            const status = agreementStatusesMap[this.value];
            return status ? status.name : this.value;
        },
        statusClass() {
            const status = agreementStatusesMap[this.value];
            return status ? status.className : '';
        }
    }
}
</script>

<template>
    <ShowcaseBadge :label="$t('_agreementLineStatus')" icon="chart-line" :background-class="backgroundClass">
        <template #value>
            <span class="badge" :class="statusClass">{{ statusName }}</span>
        </template>
    </ShowcaseBadge>
</template>

<style scoped lang="scss">
</style>