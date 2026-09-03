<template>
    <vueDP
        ref="picker"
        v-model="innerDate"
        type="date"
        :lang="getTranslations"
        :range="isRange"
        :width="width"
        use-utc="true"
        :show-week-number="true"
        :formatter="formatter"
        :disabled="isDisabled"
        :placeholder="placeholder"
        :popup-class="showPresets ? 'has-presets' : ''"
    >
        <template v-if="showPresets" #sidebar>
            <div class="date-presets">
                <button
                    v-for="preset in presetOptions"
                    :key="preset.key"
                    type="button"
                    class="date-presets__item"
                    :class="{ 'date-presets__item--active': activePreset === preset.key }"
                    @click="applyPreset(preset.key)"
                >{{ preset.label }}</button>
            </div>
        </template>
    </vueDP>
</template>

<script>


    import vueDP from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import moment from 'moment';
    import { isPreset, presetList, resolveDateRange } from '@/services/dateRangePresets';

    export default {
        name: "DatePicker",

        props: {
            value: {},
            isRange: {
                type: Boolean,
                default: true
            },
            isDisabled: {
                type: Boolean,
                default: false
            },
            dateOnly: {
                type: Boolean,
                default: true
            },
            width: {
                type: String,
                default: 'auto'
            },
            placeholder: {
                type: String,
                default: ''
            },
            /** Lista skrótów („bieżący miesiąc"...) emitujących zakres relatywny. */
            presets: {
                type: Boolean,
                default: false
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

            showPresets() {
                return this.presets && this.isRange;
            },

            presetOptions() {
                return presetList();
            },

            activePreset() {
                return isPreset(this.value) ? this.value.preset : null;
            },

            innerDate: {
                get() {
                    if (!this.value) {
                        return null;
                    }

                    if (this.isRange) {
                        const range = resolveDateRange(this.value);

                        if (!range.start && !range.end) {
                            return null;
                        }

                        return [
                            range.start ? new Date(range.start) : null,
                            range.end ? new Date(range.end) : null
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

        methods: {
            applyPreset(key) {
                this.$emit('input', { preset: key });

                if (this.$refs.picker) {
                    this.$refs.picker.closePopup();
                }
            }
        },
    }
</script>

<style scoped lang="scss">

    .date-presets {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;

        &__item {
            padding: 0.2rem 0.4rem;
            border: 0;
            border-radius: 3px;
            background: none;
            color: #73879c;
            font-size: 0.8rem;
            text-align: left;
            white-space: nowrap;
            cursor: pointer;

            &:hover {
                background-color: #f3f6f9;
            }

            &--active {
                color: var(--colorPrimary, #4e73df);
                font-weight: 600;
            }
        }
    }

</style>

<style lang="scss">

    // Popup ląduje w body, poza zasięgiem stylów scoped.
    .mx-datepicker-main.has-presets .mx-datepicker-sidebar {
        width: 130px;
        padding: 6px;
    }

    .mx-datepicker-main.has-presets .mx-datepicker-content {
        margin-left: 130px;
    }

</style>