<template>
    <vue-select
        :value="value"
        :options="options"
        :reduce="option => option.id"
        label="name"
        multiple
        :placeholder="placeholder"
        class="options-multi-select"
        @input="$emit('input', $event)"
    >
        <template #no-options>{{ $t('listing.noOptions') }}</template>
    </vue-select>
</template>

<script>
import VueSelect from 'vue-select';

export default {
    name: "OptionsMultiSelect",

    components: { VueSelect },

    props: {
        /** Wybrane id. */
        value: {
            type: Array,
            default: () => [],
        },
        /** Lista `{ id, name }`. */
        options: {
            type: Array,
            default: () => [],
        },
        placeholder: {
            type: String,
            default: '',
        },
    },
};
</script>

<style scoped lang="scss">
.options-multi-select {
    min-width: 210px;

    :deep(.vs__dropdown-toggle) {
        min-height: 36px;
    }

    :deep(.vs__search::placeholder) {
        color: #6c757d;
    }
}
</style>
