<script>
import VueSelect from 'vue-select'
import { getUserDepartments } from '@/helpers'
import proxyValue from '@/mixins/proxyValue'

export default {
    name: 'ScheduleProductionFilters',

    mixins: [proxyValue],

    props: {
        agreementLines: {
            type: Array,
            default: () => []
        }
    },

    components: {
        VueSelect
    },

    computed: {
        departmentOptions() {
            return getUserDepartments()
        },

        customerOptions() {
            const options = this.agreementLines
                .map(line => ({
                    value: line.customer.id,
                    label: line.customer.name,
                }));
            const unique = Array.from(new Map(options.map(o => [o.value, o])).values())
            return unique.sort((a, b) => a.label.localeCompare(b.label))
        },

        agreementLineOptions() {
            const agreementLines = this.proxyData.customerId.length
                ? this.agreementLines.filter(line => this.proxyData.customerId.includes(line.customer.id))
                : this.agreementLines

            return agreementLines
                .map(line => ({
                    value: line.agreementLineId,
                    label: `${line.orderNumber} - ${line.productName}`,
                }))
                .sort((a, b) =>  b.value - a.value)
        },
    },

    watch: {
        'proxyData.customerId': {
            handler() {
                if (this.proxyData.agreementLineId.length) {
                    this.proxyData.agreementLineId = []
                }
            },
            deep: true
        }
    },

}
</script>

<template>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>Dział produkcji</label>
                <vue-select
                    :options="departmentOptions"
                    :multiple="true"
                    :filterable="false"
                    :reduce="opt => opt.slug"
                    v-model="proxyData.departmentSlug"
                    label="name"
                    placeholder="Wyberz działy produkcji"
                    class="style-chooser"
                />
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Klient</label>
                <vue-select
                    :options="customerOptions"
                    :multiple="true"
                    :filterable="true"
                    :reduce="opt => opt.value"
                    v-model="proxyData.customerId"
                    label="label"
                    placeholder="Wybierz klientów"
                    class="style-chooser"
                />
            </div>
        </div>

        <div class="col-md-5">
            <div class="form-group">
                <label>Zamówienie</label>
                <vue-select
                    :options="agreementLineOptions"
                    :multiple="true"
                    :filterable="true"
                    :reduce="opt => opt.value"
                    v-model="proxyData.agreementLineId"
                    label="label"
                    placeholder="Wybierz zamówienia"
                    class="style-chooser"
                />
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>