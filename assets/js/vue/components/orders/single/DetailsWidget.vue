<template>
    <div class="sb-info-card">

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Status</label><br>

                        <div v-for="(singleStatus, key) in statuses">
                            <label>
                                <input type="radio" :value="key" v-model="inner.status" :disabled="!canEditStatus()">
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
                    <date-picker v-model="inner.confirmedDate" :is-range="false"/>
                </div>

            </div>
        </div>

        <div class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label>{{ $t('orders.requirements') }}</label>
                    <textarea class="form-control" cols="30" rows="7" v-model="inner.description"/>
                </div>

            </div>
        </div>
    </div>

</template>

<script>
    import DatePicker from "../../base/datepicker";

    export default {
        name: "DetailsWidget",
        props: ['value', 'statuses'],
        components: { DatePicker },

        data() {
            return {

                inner: {
                    status: 0,
                    description: '',
                    confirmedDate: '',
                },

            }
        },

        watch: {
            value: {
                handler(val) {
                    this.init(val);
                },
                deep: true
            },

            inner: {
                handler(val) {
                    this.$emit('input', val);
                },
                deep: true
            }
        },

        mounted() {
            this.init(this.value);
        },

        methods: {
            init(src) {
                this.inner.status = src.status;
                this.inner.description = src.description;
                this.inner.confirmedDate = src.confirmedDate;
            },

            canEditStatus() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        }


    }
</script>

<style scoped>

</style>