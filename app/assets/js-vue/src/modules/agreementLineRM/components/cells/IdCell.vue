<script>
import cellMixin from "./cellMixin"
import Tag from "@/modules/tags/widget/Tag"
import { orderDisplayNumber } from "@/helpers"

const STATUS_CLASSES = {
    5: 'badge-info',
    10: 'badge-primary',
    15: 'badge-warning',
    20: 'badge-success',
}

const STATUS_DEFAULT = 10

export default {
    name: "IdCell",

    mixins: [cellMixin],

    components: { Tag },

    props: {
        taskStatuses: {
            type: Object,
            default: () => ({})
        }
    },

    computed: {
        displayNumber() {
            const agreement = this.record.agreement || {};
            return orderDisplayNumber(agreement.orderNumber, this.record.internalNumber) || this.record.agreementLineId;
        },

        showStatus() {
            return this.record.status !== STATUS_DEFAULT && Boolean(this.taskStatuses[this.record.status]);
        },

        statusClass() {
            return STATUS_CLASSES[parseInt(this.record.status)] || 'badge-primary';
        },

        statusName() {
            return this.taskStatuses[this.record.status];
        }
    }
}
</script>

<template>
    <div>
        <span class="text-nowrap">{{ displayNumber }}</span>
        <div class="d-inline-flex">
            <tag
                v-for="(tag, key) in record.tags"
                :key="key"
                :color="tag.color"
                :icon="tag.icon"
                :name="tag.name"
            />
        </div>
        <div class="badge" :class="statusClass" v-if="showStatus">{{ $t(statusName) }}</div>
    </div>
</template>
