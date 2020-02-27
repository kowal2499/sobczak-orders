<template>
    <div class="card shadow mb-4 overflow-hidden">

        <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary" v-text="title"
            ></h6>
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <slot name="header" v-if="!collapsed">

                </slot>
                <button class="btn btn-i px-2" @click="collapsed = !collapsed">
                    <i class="fa fa-chevron-down" aria-hidden="true"
                       :class="{'fa-rotate-180': collapsed}"

                    ></i>
                </button>
            </div>
        </div>


        <div class="card-body" v-show="collapsed === false">

            <div class="card-filters">
                <slot name="filters"></slot>
            </div>

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

    button.btn:focus {
        outline: none;
        box-shadow: none !important;
    }

</style>