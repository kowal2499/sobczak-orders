<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import DescriptionInfo from "@/components/base/DescriptionInfo.vue"

export default {
    name: "ProductCell",

    mixins: [cellMixin],

    components: { DescriptionInfo },

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
        <DescriptionInfo v-if="record.description" :description="record.description" />
    </div>
</template>
