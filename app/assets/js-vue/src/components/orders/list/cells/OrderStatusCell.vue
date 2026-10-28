<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import { agreementStatusesMap } from "@/helpers"

export default {
    name: "OrderStatusCell",

    mixins: [cellMixin],

    computed: {
        status() {
            return agreementStatusesMap[parseInt(this.record.status)] || null;
        },

        label() {
            return this.status ? this.status.name : this.record.status;
        },

        badgeClass() {
            return this.status ? this.status.className : 'badge-secondary';
        }
    }
}
</script>

<template>
    <span class="badge" :class="badgeClass">{{ label }}</span>
</template>
