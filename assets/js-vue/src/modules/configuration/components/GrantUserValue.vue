<script lang="ts">
import { defineComponent } from 'vue'
import proxyValue from '@/mixins/proxyValue'
import GrantValue from './GrantValue'

export default defineComponent({
    name: "GrantUserValue",
    props: {
        grant: {
            type: Object,
            required: true
        },
        userId: {
            type: Number,
            required: true
        }
    },
    mixins: [ proxyValue ],

    components: {
        GrantValue,
    },

    watch: {
        roleMode(newValue)  {
            if (newValue) {
                this.proxyData = []
            }
        },
    },

    data: () => ({
        roleMode: true,
        localGrantValue: []
    })
})
</script>

<template>
    <div>
        <b-form-group>
            <b-form-radio-group
                size="sm"
                button-variant="outline-primary"
                v-model="roleMode"
                :options="[{text: 'Na podstawie ról', value: true}, {text: 'Ustawienie własne', value: false}]"
                name="radios-btn-default"
                buttons
            ></b-form-radio-group>
        </b-form-group>
        <GrantValue
            v-if="!roleMode"
            :grant="grant"
            :user-id="userId"
            v-model="proxyData"
        >
        </GrantValue>
    </div>
</template>

<style scoped lang="scss">

</style>