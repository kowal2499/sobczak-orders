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
        proxyData: {
            immediate: true,
            deep: true,
            handler() {
                this.roleMode = this.proxyData.length === 0
            }
        },
    },

    methods: {
        onModeChange() {
            if (this.roleMode) {
                this.proxyData = []
            }
        }
    },

    data: () => ({
        roleMode: true,
    })
})
</script>

<template>
    <div class="my-3">
        <b-form-group class="m-0">
            <b-form-radio-group
                size="sm"
                button-variant="outline-primary"
                v-model="roleMode"
                :options="[{text: 'Na podstawie ról', value: true}, {text: 'Ustawienie własne', value: false}]"
                name="radios-btn-default"
                buttons
                @change="onModeChange"
            ></b-form-radio-group>
        </b-form-group>
        <GrantValue
            v-if="roleMode"
            :grant="grant"
            :user-id="userId"
            :value="[]"
            :is-disabled="true"
            :disabledDescription="'Aby zmienić uprawnienie, przejdź do konfiguracji ról. Możesz też włączyć tryb \'ustawienie własne\' by określić uprawnienia tylko dla tego użytkownika.'"
            class="my-2"
        />
        <GrantValue
            v-if="!roleMode"
            :grant="grant"
            :user-id="userId"
            v-model="proxyData"
            class="my-2"
        />
    </div>
</template>

<style scoped lang="scss">

</style>