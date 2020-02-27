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

        <div class="col">
            <slot></slot>
        </div>
    </div>

</template>

<script>

    import DatePicker from '../../base/DatePicker';

    export default {
        name: "filters",
        props: ['value'],

        components: { DatePicker },

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

    .form-group {
        label {
            color: #4e73df;
        }

        .outline {
            padding: 5px;
            padding-left: 10px;
            border-radius: 3px;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
        }
    }
</style>