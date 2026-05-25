<template>
    <ChangeContent :title="title" :rows="rows" />
</template>

<script>
import ChangeContent from './ChangeContent.vue';

export default {
    name: 'ProductionDateChangedContent',
    components: { ChangeContent },
    props: {
        log: {
            type: Object,
            required: true,
        },
    },
    computed: {
        params() {
            return this.log.contentParams || {};
        },
        isEndDate() {
            return this.log.type === 'agreement_line.production_date_end_changed';
        },
        title() {
            const key = this.isEndDate
                ? 'agreement.activityLog.changes.productionDateEnd'
                : 'agreement.activityLog.changes.productionDateStart';
            return this.$t(key, { department: this.params.departmentName || '' });
        },
        rows() {
            return [{ old: this.params.oldDate, new: this.params.newDate }];
        },
    },
};
</script>
