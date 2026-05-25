<template>
    <div class="agreement-updated">
        <div class="header">{{ log.content }}</div>

        <ul v-if="agreementChanges.length" class="change-list">
            <li v-for="(change, i) in agreementChanges" :key="`a-${i}`">
                {{ formatAgreementChange(change) }}
            </li>
        </ul>

        <div v-for="group in lineGroups" :key="`line-${group.lineId}`" class="line-group">
            <div class="line-label">{{ lineLabel(group) }}</div>
            <ul class="change-list">
                <li v-for="(change, i) in group.changes" :key="`l-${group.lineId}-${i}`">
                    {{ formatFieldChange(change) }}
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
        formatAgreementChange(change) {
            if (change.field === 'attachmentAdded') {
                return this.$t('agreement.activityLog.changes.attachmentAdded', { name: change.value });
            }
            if (change.field === 'attachmentRemoved') {
                return this.$t('agreement.activityLog.changes.attachmentRemoved', { name: change.value });
            }
            return this.formatFieldChange(change);
        },
        formatFieldChange(change) {
            return `${this.fieldLabel(change.field)}: ${this.displayValue(change.old)} → ${this.displayValue(change.new)}`;
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
</style>
