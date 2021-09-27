<template>
    <b-modal v-model="valueProxy" @shown="init">
        <font-awesome-icon v-if="busy" icon="spinner" spin/>
       test
    </b-modal>
</template>

<script>
export default {
    name: "DetailsModal",
    props: {
        value: Boolean,
        recordsPromise: Promise
    },
    methods: {
        init() {
            this.busy = true;
            this.recordsPromise
                .then(() => {

                })
                .finally(() => this.busy = false)
        }
    },
    computed: {
        valueProxy: {
            get() {
                return this.value
            },
            set(v) {
                this.$emit('input', v)
            }
        }
    },
    data: () => ({
        busy: false
    })
}
</script>

<style scoped>

</style>