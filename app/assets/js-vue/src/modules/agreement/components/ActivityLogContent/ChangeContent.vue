<template>
    <div class="change-content">
        <div class="header">{{ title }}</div>
        <ul class="change-list">
            <li v-for="(row, i) in rows" :key="i">
                <span v-if="row.label" class="label">{{ row.label }}: </span><template v-if="hasOld(row)">{{ displayValue(row.old) }} <i class="fa fa-long-arrow-right change-arrow" aria-hidden="true"></i> </template>{{ displayValue(row.new) }}
            </li>
        </ul>
    </div>
</template>

<script>
const MAX_VALUE_LENGTH = 60;

// Shared presentational renderer for "value change" logs:
// a header line followed by one or more "old → new" rows.
export default {
    name: 'ChangeContent',
    props: {
        title: {
            type: String,
            required: true,
        },
        rows: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        // An initial value (e.g. the first production status) has no predecessor: skip the "old → " part.
        hasOld(row) {
            return row.old !== null && row.old !== undefined;
        },
        displayValue(value) {
            const v = (value ?? '').toString();
            if (v === '') {
                return this.$t('agreement.activityLog.changes.emptyValue');
            }
            return v.length > MAX_VALUE_LENGTH ? `${v.slice(0, MAX_VALUE_LENGTH)}…` : v;
        },
    },
};
</script>

<style lang="scss" scoped>
.change-content {
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

.label {
    color: #343a40;
}

.change-arrow {
    margin: 0 0.25rem;
    color: #adb5bd;
}
</style>
