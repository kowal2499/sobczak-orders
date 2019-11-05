<template>
    <vueDP
        v-model="innerDate"
        :lang="getTranslations"
        :range="isRange"
        :width="width"
        type="date"
        format="YYYY-MM-DD"
        use-utc="true"
        :first-day-of-week="Number(1)">
    </vueDP>
</template>

<script>

    import vueDP from 'vue2-datepicker';
    import moment from 'moment';

    export default {
        name: "datepicker",

        props: {
            value: {},

            isRange: {
                type: Boolean,
                default: true
            },

            width: {
                type: String,
                default: '230px'
            }
        },

        components: { vueDP },

        data() {
            return {
                innerDate: this.value,

                lang: {
                    pl : {
                        days: ['Nie', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'So'],
                        months: ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
                        pickers: ['następne 7 dni', 'następne 30 dni', 'wcześniejsze 7 dni', 'wcześniejsze 30 dni'],
                        placeholder: {
                            date: 'Wybierz datę',
                            dateRange: 'Wybierz zakres'
                        }
                    },
                    en: null
                }
            }
        },

        computed: {
            getTranslations() {
                return this.lang[this.$user.user.locale] || 'en';
            }
        },

        watch: {
            innerDate(val) {

                if (this.isRange) {
                    if (val[0] || val[1]) {
                        this.value.start = moment(val[0]).format('YYYY-MM-DD');
                        this.value.end = moment(val[1]).format('YYYY-MM-DD');
                    }
                } else {
                    this.$emit('input', moment(val).format('YYYY-MM-DD'));
                }
            },

            value: {
                handler(val) {

                    if (this.isRange) {
                        this.innerDate = [
                            val.start,
                            val.end
                        ]
                    } else {
                        this.innerDate = val;
                    }
                },
                deep: true
            }
        }
    }
</script>

<style scoped>

</style>