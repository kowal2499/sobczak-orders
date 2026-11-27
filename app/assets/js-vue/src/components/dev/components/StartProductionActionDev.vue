<script>
import { defineComponent } from 'vue'
import StartProductionAction from '@/modules/production/components/StartProductionAction.vue'

export default defineComponent({
    name: 'StartProductionActionDev',
    components: {
        StartProductionAction
    },
    computed: {
        agreementLine() {
            return {
                id: 213,
                confirmedDate: '2025-12-01',
            }
        },
    },
    data: () => ({
        show: false
    })
})
</script>

<template>
    <div class="py-3">
        <button class="btn btn-primary" @click.prevent="show = true">
            <i class="fa fa-play pr-2" aria-hidden="true"/>
            {{ $t('startProduction') }}
        </button>
        <StartProductionAction
            :agreement-line="agreementLine"
            v-model="show"
        />
    </div>
</template>

<style scoped lang="scss">

</style>