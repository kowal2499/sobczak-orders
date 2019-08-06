<template>
    <div class="card shadow mb-4">

        <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between" @click="collapsed = !collapsed">
            <h6 class="m-0 font-weight-bold text-primary" v-text="title"
            ></h6>
            <i class="fa fa-chevron-down" aria-hidden="true"
               :class="{'fa-rotate-180': collapsed}"

            ></i>
        </div>

        <div class="card-body" v-show="collapsed === false">

            <slot></slot>

        </div>

    </div>
</template>

<script>
    export default {
        name: "CollapsibleCard",

        props: ['title', 'locked'],

        data() {
            return {
                collapsed: false
            }
        }


    }
</script>

<style lang="scss" scoped>

    .card-header {
        cursor: pointer;
        .fa {
            padding: 5px;
            transition: all 0.2s;
        }
    }

</style>