<template>
    <div class="sb-info-card" v-if="value">
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Status</label><br>

                    <div v-for="(singleStatus, key) in statuses">
                        <label>
                            <input type="radio" :value="key" v-model="status" :disabled="!canEditStatus()">
                            {{ singleStatus }}
                        </label>
                    </div>

                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ $t('deliveryDate') }}</label><br>
                    <date-picker v-model="confirmedDate" :is-range="false" :date-only="false"/>
                </div>
            </div>
            <div class="col-md-8" v-if="canEditStatus()">
                <div class="form-group">
                    <label>{{ $t('orders.resourceAssignment') }}</label><br>
                    <date-picker v-model="factorBindDate" :is-range="false" :date-only="false"/>
                    <small class="form-text text-muted">{{ $t('orders.resourceAssignmentDesc') }}</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label>{{ $t('orders.requirements') }}</label>
                    <textarea class="form-control" cols="30" rows="7" v-model="description"/>
                </div>

            </div>
        </div>
    </div>

</template>

<script>
    import DatePicker from "../../base/DatePicker";

    export default {
        name: "DetailsWidget",
        props: ['value', 'statuses'],
        components: { DatePicker },

        computed: {
            status: {
                get() {
                    return parseInt(this.value.status);
                },

                set(newVal) {
                    this.emitter({status: parseInt(newVal)})
                }
            },

            description: {
                get() {
                    return this.value.description;
                },

                set(newVal) {
                    this.emitter({description: newVal})
                }
            },

            confirmedDate: {
                get() {
                    return this.value.confirmedDate;
                },

                set(newVal) {
                    this.emitter({confirmedDate: newVal})
                }
            },

            factorBindDate: {
                get() {
                    return this.value.factorBindDate;
                },

                set(newVal) {
                    this.emitter({factorBindDate: newVal})
                }
            },
        },

        methods: {
            emitter(val) {
                this.$emit('input', {
                    ...this.value,
                    ...val
                })
            },

            canEditStatus() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        }


    }
</script>

<style scoped>

</style>