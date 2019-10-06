<template>

        <li class="nav-item expandable" :class="{'expanded': expanded, 'active': active}">

            <a :href="path" class="nav-link d-flex justify-content-between align-items-center" @click.prevent="expanded = !expanded">

                <span>
                    <i :class="titleIcon" aria-hidden="true"></i>
                    <span>{{ title }}</span>
                </span>

                <i class="fa fa-chevron-right mr-3" aria-hidden="true" :class="{'fa-rotate-90': expanded}"></i>
            </a>

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
    .nav-item.expandable:hover {
        cursor: pointer;
    }

    i {
        transition: all 0.2s;
        color: rgba(255, 255, 255, 0.6);
    }
</style>