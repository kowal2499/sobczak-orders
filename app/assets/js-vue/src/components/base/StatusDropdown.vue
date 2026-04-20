<script>
export default {
    name: "StatusDropdown",

    props: {
        value: {
            default: null,
        },
        options: {
            type: Array,
            required: true,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        placeholder: {
            type: String,
            default: "Status",
        },
    },

    computed: {
        activeOption() {
            return this.options.find(opt => opt.value === this.value) || null;
        },
    },

    methods: {
        select(opt) {
            this.$emit("input", opt.value);
        },
    },
};
</script>

<template>
    <b-dropdown
        size="sm"
        :disabled="disabled"
        class="status-dropdown"
        variant="outline-primary"
    >
        <template #button-content>
            <font-awesome-icon
                v-if="activeOption"
                icon="square"
                :style="{ color: activeOption.color }"
            />
            <span>{{ activeOption ? activeOption.name : placeholder }}</span>
        </template>

        <b-dropdown-item
            v-for="opt in options"
            :key="opt.value"
            :active="opt.value === value"
            :disabled="!!opt.disabled"
            @click="select(opt)"
        >
            <font-awesome-icon
                icon="square"
                class="status-dropdown__icon"
                :style="{ color: opt.color }"
            />
            {{ opt.name }}
        </b-dropdown-item>
    </b-dropdown>
</template>

<style scoped lang="scss">
.status-dropdown {
    width: 100%;

    ::v-deep .btn {
        width: 100%;
        background-color: var(--colorWhite);
        color: var(--colorPrimary);
        display: flex;
        align-items: center;
        gap: 0.4rem;

        span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        &:active {
            background-color: var(--colorWhite);
            color: var(--colorPrimary);
        }
    }
}

.status-dropdown__icon {
    flex-shrink: 0;
    border: 1px solid #ccc;
    border-radius: 2px;
    width: 1em !important;
    height: 1em !important;
    overflow: hidden;
}
</style>
