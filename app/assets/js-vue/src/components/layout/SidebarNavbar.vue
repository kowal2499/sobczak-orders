<script>
import { defineComponent } from 'vue'
import { debounce } from 'lodash'

export default defineComponent({
    name: "SidebarNavbar",
    props: {
        showExcelExportBtn: {
            type: Boolean,
            default: true
        }
    },
    watch: {
        q: {
            handler(newValue) {
                this.debouncedEmit(newValue)
            }
        }
    },
    created() {
        this.debouncedEmit = debounce(val => {
            this.$emit('search', val)
        }, 300)
    },
    data: () => ({
        q: null
    })
})
</script>

<template>
    <div class="sidebar-navbar">
        <slot name="controls" />
        <div class="sidebar-navbar__search" role="search">
            <input
                v-model="q"
                type="search"
                class="form-control form-control-sm"
                placeholder="Szukaj..."
                aria-label="Szukaj"
            />
            <i class="fa fa-search" aria-hidden="true"></i>
        </div>
        <button v-if="showExcelExportBtn" type="button" class="btn btn-outline-primary btn-sm" @click.prevent="$emit('exportExcel')">
            <font-awesome-icon icon="download" class="mr-1" />Excel
        </button>
    </div>
</template>

<style scoped lang="scss">
.sidebar-navbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-navbar__search {
    position: relative;
    width: 16rem;
    max-width: 100%;

    // same box as the neighbouring .btn-sm (_forms.scss): 0.85rem text, 0.25rem vertical padding
    .form-control {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 1.75rem 0.25rem 0.5rem;
        font-size: 0.85rem;
        background-color: var(--colorSurface);
    }

    .fa-search {
        position: absolute;
        top: 50%;
        right: 0.6rem;
        transform: translateY(-50%);
        font-size: 0.75rem;
        color: var(--colorSecondary);
        pointer-events: none;
    }
}
</style>
