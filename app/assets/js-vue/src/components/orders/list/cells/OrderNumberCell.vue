<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import TagsIndicator from "@/modules/tags/widget/TagsIndicator"
import { orderDisplayNumber } from "@/helpers"

export default {
    name: "OrderNumberCell",

    mixins: [cellMixin],

    components: { TagsIndicator },

    computed: {
        agreement() {
            return this.record.Agreement || {};
        },

        displayNumber() {
            return orderDisplayNumber(this.agreement.orderNumber, this.record.internalNumber) || this.agreement.id;
        }
    }
}
</script>

<template>
    <div class="d-flex flex-column">
        <span class="text-nowrap">{{ displayNumber }}</span>
        <tags-indicator :logs="record.tags" />
    </div>
</template>
