<template>
    <div class="sb-info-card" v-if="value">
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Status</label><br>
                    <div v-for="(singleStatus, key) in taskStatuses" :key="key">
                        <label>
                            <input type="radio" :value="key" v-model="status" disabled>
                            {{ singleStatus }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label>{{ $t('deliveryDate') }}</label><br>
                    <date-picker v-model="confirmedDate" :is-range="false" :date-only="false"/>
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
        props: {
            value: Object,
            taskStatuses: Object,
            hasGhost: {
                type: Boolean,
                default: false
            }
        },

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
            }
        },

        methods: {
            emitter(val) {
                this.$emit('input', {
                    ...this.value,
                    ...val
                })
            }
            // Status is read-only in this panel. It is changed only via dedicated flows:
            // archive/warehouse/restore/delete from the order list, and "start production".
            // The update endpoint (/agreement_line/update) ignores any incoming status change.
        }
    }
</script>

<style scoped>

</style>