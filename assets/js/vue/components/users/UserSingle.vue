<template>

    <div class="row">
        <div class="col-sm-12">
            <collapsible-card
                    :title="firstName + ' ' + lastName"
                    :locked="locked"
            ></collapsible-card>
        </div>

    </div>

</template>

<script>

    import CollapsibleCard from "../base/CollapsibleCard";

    export default {
        name: "UserSingle",

        components: { CollapsibleCard },

        props: [ 'first-name', 'last-name' ],

        data() {
            return {
                locked: false
            }
        }

    }
</script>

<style scoped>

</style>