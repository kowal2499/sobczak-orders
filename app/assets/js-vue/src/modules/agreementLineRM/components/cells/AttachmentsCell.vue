<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import Attachments from "../Attachments"
import NoData from "@/components/base/BaseListing/components/cells/NoData"

export default {
    name: "AttachmentsCell",

    mixins: [cellMixin],

    components: { Attachments, NoData },

    computed: {
        attachments() {
            return this.record.attachments || [];
        }
    }
}
</script>

<template>
    <Attachments v-if="attachments.length > 0" :attachments="attachments" />
    <NoData v-else :label="$t('orders.noattachments')" />
</template>
