<template>
    <div class="d-flex flex-wrap align-items-center justify-content-end">
        <form class="list-search mb-0" role="search" @submit.prevent="$emit('search', term.trim())">
            <input
                v-model="term"
                type="search"
                autocomplete="off"
                class="form-control form-control-sm list-search__input"
                :placeholder="placeholder"
                :aria-label="searchLabel"
            >
            <button type="submit" class="list-search__submit" :title="searchLabel" :aria-label="searchLabel">
                <i class="fa fa-search" aria-hidden="true"></i>
            </button>
        </form>

        <router-link v-if="newTo" :to="newTo" class="btn btn-primary btn-sm d-inline-flex align-items-center ml-3 text-nowrap">
            <i class="fa fa-plus mr-2" aria-hidden="true"></i> {{ newLabel }}
        </router-link>
    </div>
</template>

<script>
export default {
    name: 'ListSearchBar',
    props: {
        value: { type: String, default: '' },
        placeholder: { type: String, default: '' },
        searchLabel: { type: String, required: true },
        newLabel: { type: String, default: '' },
        newTo: { type: Object, default: null },
    },
    data() {
        return { term: this.value }
    },
    watch: {
        value(value) {
            this.term = value
        },
    },
}
</script>

<style scoped lang="scss">
.list-search {
    position: relative;
    width: 16rem;
    max-width: 100%;
}

.list-search__input {
    padding-right: 2rem;
    background-color: var(--colorSurface);
}

.list-search__submit {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 2rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--colorSecondary);
    cursor: pointer;

    &:hover {
        color: var(--colorPrimary);
    }

    &:focus-visible {
        outline: 2px solid var(--colorPrimary);
        outline-offset: -2px;
        border-radius: 0.35rem;
    }
}
</style>
