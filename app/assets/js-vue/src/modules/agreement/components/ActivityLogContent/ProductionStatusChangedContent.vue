<template>
    <ChangeContent :title="title" :rows="rows" />
</template>

<script>
import ChangeContent from './ChangeContent.vue';

export default {
    name: 'ProductionStatusChangedContent',
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
        title() {
            return this.$t('agreement.activityLog.changes.productionStatus', {
                department: this.params.departmentName || '',
            });
        },
        rows() {
            const emptyValue = this.$t('agreement.activityLog.changes.emptyValue');
            const rawOld = this.params.oldStatusName;
            // No predecessor: new logs store null; legacy logs stored the em-dash placeholder.
            const hasOld = rawOld !== null && rawOld !== undefined && rawOld !== emptyValue;

            return [{
                old: hasOld ? rawOld : null,
                new: this.params.newStatusName ?? this.params.statusName ?? '',
            }];
        },
    },
};
</script>
