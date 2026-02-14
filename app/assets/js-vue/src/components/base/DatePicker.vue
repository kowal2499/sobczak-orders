<template>
    <vueDP
        v-model="innerDate"
        type="date"
        :lang="getTranslations"
        :range="isRange"
        :width="width"
        use-utc="true"
        :show-week-number="true"
        :formatter="formatter"
    />
</template>

<script>


    import vueDP from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import moment from 'moment';

    export default {
        name: "DatePicker",

        props: {
            value: {},

            isRange: {
                type: Boolean,
                default: true
            },

            dateOnly: {
                type: Boolean,
                default: true
            },

            width: {
                type: String,
                default: 'auto'
            }
        },

        components: { vueDP },

        data() {
            return {

                lang: {
                    pl : {
                        days: ['Nie', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'So'],
                        pickers: ['następne 7 dni', 'następne 30 dni', 'wcześniejsze 7 dni', 'wcześniejsze 30 dni'],
                        placeholder: {
                            date: 'Wybierz datę',
                            dateRange: 'Wybierz zakres'
                        },
                        formatLocale: {
                            firstDayOfWeek: 1,
                            monthsShort: ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
                        },
                    },
                    en: {
                        formatLocale: {
                            firstDayOfWeek: 1,
                        }
                    },

                },
            }
        },

        computed: {
            getTranslations() {
                return this.lang[this.$user.user.locale] || this.lang['en'];
            },

            formatter: () => ({
                getWeek: date => moment(date).isoWeek()
            }),

            innerDate: {
                get() {
                    if (!this.value) {
                        return null;
                    }

                    if (this.isRange) {
                        return [
                            new Date(String(this.value.start)),
                            new Date(String(this.value.end))
                        ]
                    } else {
                        if (this.dateOnly) {
                            return new Date(moment(this.value).format('YYYY-MM-DD'));
                        } else {
                            return new Date(moment(this.value).format('YYYY-MM-DD HH:mm:ss'));
                        }
                    }
                },

                set(newValue) {
                    if (this.isRange) {
                        this.$emit('input', {
                            start: newValue[0] ? moment(newValue[0]).format('YYYY-MM-DD') : null,
                            end: newValue[1] ? moment(newValue[1]).format('YYYY-MM-DD') : null,
                        });
                    } else {
                        if (this.dateOnly) {
                            this.$emit('input', newValue ? moment(newValue).format('YYYY-MM-DD') : null);
                        } else {
                            this.$emit('input', newValue ? moment(newValue).format('YYYY-MM-DD HH:mm:ss') : null);
                        }

                    }
                }
            }
        },

    }
</script>

<style scoped>

</style>