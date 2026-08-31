<script>
import cellMixin from "./cellMixin"
import NoData from "./NoData"

export default {
    name: "UserCell",

    mixins: [cellMixin],

    components: { NoData },

    computed: {
        userName() {
            return (this.record.user || {}).name;
        }
    }
}
</script>

<template>
    <span v-if="userName">{{ userName }}</span>
    <NoData v-else />
</template>
