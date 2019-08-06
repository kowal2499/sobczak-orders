<template>
    <vueDP v-model="date" :lang="lang" type="date" format="YYYY-MM-DD" use-utc="true" :first-day-of-week="Number(1)"></vueDP>
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
                date: this.value,

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
            date(val) {
                
                if (val) {
                    let formatted = new moment(val);

                    if (formatted.isValid()) {
                        val = formatted.format('YYYY-MM-DD');
                    }
                }
                
                this.$emit('input', val);
            },

            value: {
                handler(val) {
                    this.date = val;
                },
                deep: true
            }
        }
    }
</script>

<style scoped>

</style>