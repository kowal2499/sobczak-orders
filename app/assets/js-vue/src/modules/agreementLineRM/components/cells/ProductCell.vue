<script>
import cellMixin from "./cellMixin"
import Tooltip from "@/components/base/Tooltip"

export default {
    name: "ProductCell",

    mixins: [cellMixin],

    components: { Tooltip },

    computed: {
        productName() {
            return (this.record.product || {}).name || this.record.productName;
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
    </div>
</template>
