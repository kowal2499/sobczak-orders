<script>
import { defineComponent } from 'vue'
import { debounce } from 'lodash'

export default defineComponent({
    name: "DetailsNavbar",
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
    <div class="d-flex flex-row justify-content-end gap-2 mx-2 my-1">
        <div>
            <input type="text" class="form-control form-control-sm" placeholder="Szukaj..." v-model="q" />
        </div>
        <div>
            <button v-if="showExcelExportBtn" class="btn btn-outline-primary btn-sm" @click.prevent="$emit('exportExcel')">
                <font-awesome-icon icon="download" /> Excel
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>