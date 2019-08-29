<template>

        <li class="nav-item expandable" :class="{'expanded': expanded, 'active': active}">

            <div class="d-flex justify-content-between align-items-center">
                <a :href="path" class="nav-link" @click.prevent="expanded = !expanded">
                    <i :class="titleIcon" aria-hidden="true"></i>
                    <span>{{ title }}</span>
                </a>

                <i class="fa fa-chevron-right mr-3" aria-hidden="true" :class="{'fa-rotate-90': expanded}"></i>
            </div>

            <slot></slot>
        </li>

</template>

<script>
    export default {
        name: "MenuItem",

        props: {
            active: {
                type: Boolean,
                default: false
            },

            path: {
                type: String
            },

            titleIcon: {
                type: String
            },

            title: {
                type: String
            }
        },

        data() {
            return {
                expanded: false
            }
        },

        mounted() {
            if (this.active) {
                this.expanded = true;
            }
        }
    }

</script>

<style scoped lang="scss">
    i {
        transition: all 0.2s;
        color: rgba(255, 255, 255, 0.6);
    }
</style>