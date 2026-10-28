<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import NoData from "@/components/base/BaseListing/components/cells/NoData"

export default {
    name: "OrderUserCell",

    mixins: [cellMixin],

    components: { NoData },

    computed: {
        userName() {
            return ((this.record.Agreement || {}).user || {}).userFullName;
        }
    }
}
</script>

<template>
    <span v-if="userName">{{ userName }}</span>
    <NoData v-else />
</template>
