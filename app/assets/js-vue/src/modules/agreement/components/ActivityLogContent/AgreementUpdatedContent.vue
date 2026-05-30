<template>
    <div class="agreement-updated">
        <div class="header">{{ log.content }}</div>

        <ul v-if="agreementChanges.length" class="change-list">
            <li v-for="(change, i) in agreementChanges" :key="`a-${i}`">
                <template v-if="isAttachment(change)">{{ attachmentMessage(change) }}</template>
                <template v-else>{{ fieldLabel(change.field) }}: {{ displayValue(change.old) }} <i class="fa fa-long-arrow-right change-arrow" aria-hidden="true"></i> {{ displayValue(change.new) }}</template>
            </li>
        </ul>

        <div v-for="group in lineGroups" :key="`line-${group.lineId}`" class="line-group">
            <div class="line-label">{{ lineLabel(group) }}</div>
            <ul class="change-list">
                <li v-for="(change, i) in group.changes" :key="`l-${group.lineId}-${i}`">
                    {{ fieldLabel(change.field) }}: {{ displayValue(change.old) }} <i class="fa fa-long-arrow-right change-arrow" aria-hidden="true"></i> {{ displayValue(change.new) }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
const MAX_VALUE_LENGTH = 60;

export default {
    name: 'AgreementUpdatedContent',
    props: {
        log: {
            type: Object,
            required: true,
        },
    },
    computed: {
        changes() {
            return this.log.contentParams?.changes || [];
        },
        agreementChanges() {
            return this.changes.filter((c) => c.scope === 'agreement');
        },
        lineGroups() {
            const groups = [];
            const byId = {};
            this.changes
                .filter((c) => c.scope === 'line')
                .forEach((c) => {
                    if (!byId[c.lineId]) {
                        byId[c.lineId] = { lineId: c.lineId, productName: c.productName, changes: [] };
                        groups.push(byId[c.lineId]);
                    }
                    byId[c.lineId].changes.push(c);
                });
            return groups;
        },
    },
    methods: {
        fieldLabel(field) {
            return this.$t(`agreement.activityLog.changes.field.${field}`);
        },
        displayValue(value) {
            const v = (value ?? '').toString();
            if (v === '') {
                return this.$t('agreement.activityLog.changes.emptyValue');
            }
            return v.length > MAX_VALUE_LENGTH ? `${v.slice(0, MAX_VALUE_LENGTH)}…` : v;
        },
        lineLabel(group) {
            return group.productName
                ? this.$t('agreement.activityLog.changes.lineLabel', { product: group.productName })
                : this.$t('agreement.activityLog.changes.lineLabelUnknown');
        },
        isAttachment(change) {
            return change.field === 'attachmentAdded' || change.field === 'attachmentRemoved';
        },
        attachmentMessage(change) {
            const key = change.field === 'attachmentAdded'
                ? 'agreement.activityLog.changes.attachmentAdded'
                : 'agreement.activityLog.changes.attachmentRemoved';
            return this.$t(key, { name: change.value });
        },
    },
};
</script>

<style lang="scss" scoped>
.agreement-updated {
    font-size: 0.9rem;
    color: #495057;
    line-height: 1.4;
}

.header {
    font-weight: 500;
    color: #212529;
}

.change-list {
    list-style: none;
    margin: 0.25rem 0 0;
    padding: 0;

    li {
        padding: 0.05rem 0;
    }
}

.line-group {
    margin-top: 0.5rem;
}

.line-label {
    font-weight: 500;
    color: #343a40;
}

.line-group .change-list {
    margin-left: 0.75rem;
}

.change-arrow {
    margin: 0 0.25rem;
    color: #adb5bd;
}
</style>
