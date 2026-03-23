<script>
import {defineComponent} from 'vue'
import StrategyCascade from "../services/productionScheduler/strategyCascade";
import StrategyInitial from "../services/productionScheduler/strategyFast";
import VueSelect from 'vue-select'

export default defineComponent({
    name: 'StrategySelect',

    components: {
        VueSelect,
    },

    mounted() {
        this.fetchStrategies()
    },

    methods: {
        fetchStrategies() {
            this.isLoading = true
            return Promise.resolve({data: [
                StrategyCascade, StrategyInitial
            ]}).then(({data}) => {
                this.strategies = data
            }).finally(() => this.isLoading = true)
        },

        onSelected(strategy) {
            this.$emit('strategySelected', strategy)
        }
    },

    data: () => ({
        isLoading: false,
        strategies: []
    })
})
</script>

<template>
    <div class="row">
        <div class="d-flex justify-content-end align-items-center col-12 col-lg-4 text-md-right">
            {{ $t('production.selectStrategyLabel') }}
        </div>
        <div class="col-12 col-lg-8">
            <VueSelect
                label="name"
                :clearable="false"
                :searchable="false"
                :placeholder="$t('choose')"
                :options="strategies"
                @option:selected="onSelected"
            >
                <template #selected-option="{ name, description}">
                    {{ $t(name) }}
                </template>
                <template #option="{ name, description}">
                    <div class="w-100 text-wrap">
                        <div class="text-body">{{ $t(name) }}</div>
                        <div class="text-muted" style="font-size: 0.85rem">{{ $t(description) }}</div>
                    </div>
                </template>
            </VueSelect>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>