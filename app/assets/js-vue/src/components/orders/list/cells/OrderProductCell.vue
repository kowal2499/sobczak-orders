<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import Tooltip from "@/components/base/Tooltip"

export default {
    name: "OrderProductCell",

    mixins: [cellMixin],

    components: { Tooltip },

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
        <tooltip v-if="record.description">
            <i slot="visible-content" class="fa fa-info-circle hasTooltip" />
            <div slot="tooltip-content" class="text-left" v-html="__mixin_convertNewlinesToHtml(record.description)" />
        </tooltip>
        <span v-if="hasAttachments"><i class="fa fa-paperclip sb-color" /></span>
    </div>
</template>
