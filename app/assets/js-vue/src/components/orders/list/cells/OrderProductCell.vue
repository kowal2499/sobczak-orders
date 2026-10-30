<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import DescriptionInfo from "@/components/base/DescriptionInfo.vue"

export default {
    name: "OrderProductCell",

    mixins: [cellMixin],

    components: { DescriptionInfo },

    computed: {
        productName() {
            return (this.record.Product || {}).name;
        },

        hasAttachments() {
            return ((this.record.Agreement || {}).attachments || []).length > 0;
        }
    }
}
</script>

<template>
    <div>
        {{ productName }}
        <DescriptionInfo v-if="record.description" :description="record.description" />
        <span v-if="hasAttachments"><i class="fa fa-paperclip sb-color" /></span>
    </div>
</template>
