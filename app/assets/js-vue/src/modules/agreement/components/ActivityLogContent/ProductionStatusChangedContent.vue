<template>
    <ChangeContent :title="title" :rows="rows" />
</template>

<script>
import ChangeContent from './ChangeContent.vue';

// Renders a production status change as:
//   "<Department>, zmiana statusu"
//   <old status> → <new status>
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
            // New logs carry oldStatusName/newStatusName; older logs only had statusName.
            const oldStatus = this.params.oldStatusName
                ?? this.$t('agreement.activityLog.changes.emptyValue');
            const newStatus = this.params.newStatusName ?? this.params.statusName ?? '';
            return [{ old: oldStatus, new: newStatus }];
        },
    },
};
</script>
