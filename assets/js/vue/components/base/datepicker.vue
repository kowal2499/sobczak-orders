<template>
    <vueDP
        v-model="innerRange"
        :lang="lang"
        :range="true"
        :width="'230px'"
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
        props: ['value'],
        components: { vueDP },

        data() {
            return {
                innerRange: [],

                lang: {
                    days: ['Nie', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'So'],
                    months: ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
                    placeholder: {
                        date: 'Wybierz datę',
                        dateRange: 'Wybierz zakres'
                    }
                }
            }
        },

        watch: {
            innerRange(val) {
                if (val[0] || val[1]) {
                    this.value.start = moment(val[0]).format('YYYY-MM-DD');
                    this.value.end = moment(val[1]).format('YYYY-MM-DD');
                }
            },

            value: {
                handler(val) {
                    this.innerRange = [
                        val.start,
                        val.end
                    ]
                },
                deep: true
            }
        }
    }
</script>

<style scoped>

</style>