<template>
    <div class="card shadow mb-4" :class="[noPadding && 'border-0']">
        <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary" v-text="title"
            ></h6>
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <slot name="header" v-if="!collapsed"></slot>
                <i class="fa fa-chevron-down ml-3" aria-hidden="true"
                   :class="{'fa-rotate-180': collapsed}"
                   @click="collapsed = !collapsed"
                ></i>
            </div>
        </div>

        <div class="card-body" :class="noPadding && 'p-0 border-0'" v-show="collapsed === false">
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

        props: {
            title: String,
            locked: {
                type: Boolean,
                default: false
            },
            noPadding: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                collapsed: false
            }
        }
    }
</script>

<style lang="scss" scoped>



</style>