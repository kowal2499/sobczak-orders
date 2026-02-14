<script>
import datePicker from "@/components/base/DatePicker.vue";
import { saveCapacity, fetchCapacities, deleteCapacity } from "../../repository/workRepository";
import CapacityHistory from "./CapacityHistory.vue";

function getForm() {
    return {
        dateFrom: null,
        capacity: null,
    }
}

export default {
    name: "DailyCapacityForm",
    components: {
        CapacityHistory,
        datePicker
    },

    mounted() {
        this.fetchHistory()
    },

    methods: {
        async onSubmit() {
            if (this.isBusy) {
                return
            }

            const result = await this.$refs.form.validate()
            if (!result) {
                return
            }

            this.isBusy = true
            return saveCapacity({
                ...this.form,
                capacity: parseFloat(this.form.capacity)
            })
            .then(() => {
                EventBus.$emit('message', {
                    type: 'success',
                    content: this.$t('saveSuccess')
                })

                return this.fetchHistory()
            })
            .then(() => {
                this.form = getForm()
                this.$refs.form.reset()
            })
            .catch(() => EventBus.$emit('message', {
                type: 'error',
                content: data.response.data.errors.title
            }))
            .finally(() => this.isBusy = false)
        },

        fetchHistory() {
            this.isBusy = true
            return fetchCapacities()
                .then(({data}) => this.capacityHistory = data || [])
                .finally(() => this.isBusy = false)
        },

        onDelete(id) {
            if (this.isBusy) {
                return
            }

            this.isBusy = true
            return deleteCapacity(id)
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('deleteSuccess')
                    })
                    return this.fetchHistory()
                })
                .catch(() => EventBus.$emit('message', {
                    type: 'error',
                    content: data.response.data.errors.title
                }))
                .finally(() => this.isBusy = false)
        }
    },
    data: () => ({
        isBusy: false,
        capacityHistory: [],
        form: getForm()
    })
}
</script>

<template>
    <b-row>
        <b-col md="7" v-if="$user.can('work-configuration.capacity')">
            <ValidationObserver ref="form" #default="{ invalid }" tag="div" class="capacity-form">
                <ValidationProvider
                    :name="$t('config.production.form.dateFromTitle')"
                    #default="{ errors }"
                    :rules="{ required: true }"
                >
                    <b-form-group :label="$t('config.production.form.dateFromTitle')" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                        <date-picker
                            v-model="form.dateFrom"
                            :is-range="false"
                            :date-only="true"
                            :class="errors.length > 0 && 'is-invalid'"
                        />
                    </b-form-group>
                </ValidationProvider>

                <ValidationProvider
                    :name="$t('config.production.form.capacityTitle')"
                    #default="{ errors }"
                    :rules="{ required: true, min_value: 0.01 }"
                >
                    <b-form-group :label="$t('config.production.form.capacityTitle')" :invalid-feedback="errors.join(' ')" :state="errors.length ? false : null">
                        <b-form-input
                            v-model="form.capacity"
                            type="number"
                            min="0"
                            :step="0.01"
                            :state="errors.length ? false : null"
                        />
                    </b-form-group>
                </ValidationProvider>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary mb-3" @click="onSubmit" :disabled="isBusy">
                        <font-awesome-icon :icon="['fas', 'spinner']" spin class="mr-1" v-if="isBusy" />
                        <font-awesome-icon :icon="['fas', 'save']" class="mr-1" v-else />
                        {{ $t('_save') }}
                    </button>
                </div>
            </ValidationObserver>
        </b-col>
        <b-col md="5">
            <CapacityHistory
                :capacity-history="capacityHistory"
                :is-busy="isBusy"
                @delete="onDelete"
            />
        </b-col>
    </b-row>
</template>

<style lang="scss">
    .capacity-form {
        .form-control, .mx-datepicker {
            width: 100% !important;
        }
    }
</style>