<template>
    <div class="d-flex flex-wrap align-items-center">
        <form class="form-inline flex-grow-1 mb-0" @submit.prevent="$emit('search', term.trim())">
            <div class="input-group">
                <input v-model="term" type="text" autocomplete="off" class="form-control" :placeholder="placeholder">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-light border">
                        <i class="fa fa-search" aria-hidden="true"></i> {{ searchLabel }}
                    </button>
                </div>
            </div>
        </form>

        <router-link v-if="newTo" :to="newTo" class="btn btn-success btn-sm d-inline-flex align-items-center ml-3 text-nowrap">
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
