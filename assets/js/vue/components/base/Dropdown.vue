<template>


    <div class="dropdown"
         v-click-outside="onClickOutside"
    >
        <button class="btn btn-light dropdown-toggle" type="button"
            @click.prevent="expanded = !expanded">
            <i :class="iconClass" aria-hidden="true"></i>
            <span v-if="btnText">&nbsp;{{ btnText }}</span>
        </button>

        <div class="dropdown-menu"
             :class="{show: expanded}"
             @click="expanded = false"
        >
            <slot></slot>
        </div>

    </div>


</template>

<script>
    export default {
        name: "Dropdown",

        props: {
            iconClass: {
                type: String,
                default: 'fa fa-bars'
            },

            btnText: {
                type: String
            }
        },

        data() {
            return {
                expanded: false
            }
        },

        directives: {
            'click-outside': {
                bind(el, binding, vnode) {
                    const handler = (e) => {

                        if (vnode.context.expanded === false) {
                            return;
                        }

                        const bubble = binding.modifiers.bubble;

                        if (bubble || (!el.contains(e.target) && el !== e.target)) {
                            binding.value(e)
                        }

                        el.__vueClickOutside__ = handler
                    };

                    document.addEventListener('mousedown', handler)
                },

                unbind(el) {
                    document.removeEventListener('mousedown', el.__vueClickOutside__);
                    el.__vueClickOutside__ = null
                }
            }
        },

        methods: {
            onClickOutside() {
                this.expanded = false;
            }
        }
    }
</script>

<style lang="scss" scoped>

    .dropdown.icon-only {
        width: 40px;
    }

    .dropdown {

        .dropdown-menu {
            left: auto;
            right: 0;
            top: 110%;

            padding: 0;

            a {
                font-size: 0.8rem;
            }

        }

        button {
            &.dropdown-toggle:after {
                display: none;
            }

            &.btn {
                padding: 0 0.5rem;
            }
        }

        a i {
            margin-right: 10px;
            color: #aaa;
        }
    }
</style>