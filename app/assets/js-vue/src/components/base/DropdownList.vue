<script>
export default {
    name: 'DropdownList',

    props: {
        items: {
            type: Array,
            required: true
        },
        showMoreLabel: {
            type: String,
            default: null
        }
    },

    computed: {
        triggerLabel() {
            return this.showMoreLabel || this.$t('_showMore');
        }
    },

    mounted() {
        this.$nextTick(() => this.patchDropdownCloseListener());
    },

    methods: {
        patchDropdownCloseListener() {
            const dropdown = this.$refs.dropdown;
            if (!dropdown) return;
            // rootCloseListener jest rejestrowany dynamicznie przy każdym otwarciu dropdowna.
            // Podmieniamy go na instancji zanim zostanie zarejestrowany — dzięki temu
            // BV2 zarejestruje naszą wersję i wewnętrzne dropdowny nie zamkną rodzica.
            dropdown.rootCloseListener = (vm) => {
                if (vm !== dropdown && dropdown.$el && dropdown.$el.contains(vm.$el)) {
                    return; // otwiera się child dropdown — zostajemy otwarci
                }
                if (vm !== dropdown) {
                    dropdown.visible = false;
                }
            };
        },
    },
};
</script>

<template>
    <div class="dropdown-list">

        <div v-if="items.length > 0" class="dropdown-list__first">
            <slot :item="items[0]" :index="0" />
        </div>

        <b-dropdown
            v-if="items.length > 1"
            ref="dropdown"
            block
            variant="link"
            size="sm"
            class="mt-1 dropdown-list__more"
        >
            <template #button-content>
                {{ triggerLabel }} ({{ items.length - 1 }})
            </template>

            <b-dropdown-text
                v-for="(item, i) in items.slice(1)"
                :key="i"
                class="dropdown-list__item"
            >
                <slot :item="item" :index="i + 1" />
            </b-dropdown-text>
        </b-dropdown>

    </div>
</template>

<style scoped lang="scss">
.dropdown-list__more {
    ::v-deep .dropdown-menu {
        font-size: inherit;
    }

    ::v-deep .btn-link {
        color: var(--colorPrimary);
        text-decoration: none;
        box-shadow: none;
        padding-left: 0;

        &:hover {
            text-decoration: underline;
        }
    }
}

.dropdown-list__item {
    margin: 0;

    ::v-deep p.b-dropdown-text {
        margin: 0;
        padding: 4px 8px;
    }

    & + & ::v-deep p.b-dropdown-text {
        border-top: 1px solid #e9ecef;
    }
}
</style>
