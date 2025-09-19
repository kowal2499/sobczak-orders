<script>
import { defineComponent } from 'vue'
import proxyValue from '@/mixins/proxyValue'
import GrantsList from './GrantsList'

export default defineComponent({
    name: 'UserGrants',
    props: {
        userId: {
            type: Number,
            required: true
        },
    },
    mixins: [ proxyValue ],
    components: {
        GrantsList
    },
    data: () => ({
        value: true
    })
})
</script>

<template>
    <GrantsList #grantValue="{ grant }">
        <b-form-group>
            <b-form-radio-group
                size="sm"
                button-variant="outline-primary"
                v-model="value"
                :options="[{text: 'Wartość wg ról', value: true}, {text: 'Ustawienie ręczne', value: false}]"
                name="radios-btn-default"
                buttons
            ></b-form-radio-group>
        </b-form-group>

<!--        :value="isBoolTypeChecked"-->
<!--        @input="setValue($event, null)"-->
    </GrantsList>
</template>

<style scoped lang="scss">

</style>