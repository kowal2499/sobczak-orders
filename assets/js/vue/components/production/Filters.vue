<template>

    <div class="form-row">

        <div class="form-group col-md-3 col-sm-12">
            <label>{{ $t('search') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <i class="fa fa-search"></i>
                    </div>
                </div>
                <input type="text" class="form-control" v-model="form.q" :placeholder="$t('orders.searchPlaceholder')">
            </div>
        </div>

        <div class="form-group col-md-3 col-sm-4">
            <label>{{ $t('receiveDate') }}</label>
            <date-picker v-model="form.dateStart"/>
        </div>

        <div class="form-group col-md-3 col-sm-4">
            <label>{{ $t('deliveryDate') }}</label>
            <date-picker v-model="form.dateDelivery"/>
        </div>

        <div class="form-group col-md-3 col-sm-4" v-if="userCanProduction">
            <label>{{ $t('factorsDate') }}</label>
            <date-picker v-model="form.dateFactors"/>
        </div>

        <div class="form-group col-sm-3">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" v-model="form.hideArchive" id="hideArchiveSwitch">
                <label class="custom-control-label" for="hideArchiveSwitch">{{ $t('orders.hideArchivedOrder') }}</label>
            </div>
        </div>

    </div>
</template>

<script>

    import DatePicker from '../base/DatePicker';
    export default {
        name: "Filters",
        components: { DatePicker },
        props: ['value'],

        watch: {
            input: {
                deep: true,
                immediate: true,
                handler() {
                    this.form = {...this.value}
                }
            },

            form: {
                deep: true,
                handler() {
                    this.$emit('input', {...this.form})
                }
            }
        },

        computed: {
            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            }
        },

        data() {
            return {
                form: {}
            }
        },
    }
</script>

<style scoped lang="scss">
</style>