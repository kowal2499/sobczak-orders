<template>
    <Sidebar
        :value="value"
        :title="$t('agreement_line_list.startProductionForm.modalTitle')"
        sidebar-class="size-100 size-lg-80"
        @input="$emit('input', $event)"
    >
        <template #sidebar-content>
            <ValidationObserver ref="form" tag="div" class="start-production">
                <div class="row">
                    <div class="col-xl-8">
                        <div class="d-flex flex-column gap-3">
                            <StrategySelect @strategySelected="onStrategySelected" />
                            <StartProductionForm
                                v-model="form"
                                :confirmedDate="confirmedDate"
                            />
                        </div>
                    </div>

                    <div class="col-xl-4 context-column">
                        <StartProductionContext :agreement-line="agreementLine" />
                    </div>
                </div>

                <ProductionPlanCalendar
                    :value="form"
                    @input="onCalendarInput"
                    class="start-production__calendar"
                    :confirmed-date="confirmedDateString"
                    :agreement-line-id="agreementLineId"
                />
            </ValidationObserver>
        </template>

        <template #sidebar-footer="{ close }">
            <div class="form-actions w-100 mt-0 p-3">
                <button type="button" class="btn btn-outline-secondary" :disabled="saving" @click="close">{{ $t('cancel') }}</button>
                <button type="button" class="btn btn-primary" :disabled="saving" @click="startProduction(close)">
                    <i v-if="saving" class="fa fa-spinner fa-spin" aria-hidden="true" />
                    {{ $t('agreement_line_list.startProductionForm.startProduction') }}
                </button>
            </div>
        </template>
    </Sidebar>
</template>

<script>
import Sidebar from "@/components/base/Sidebar.vue";
import ProductionPlanCalendar from "./ProductionPlanCalendar.vue";
import StartProductionForm from "./StartProductionForm.vue";
import StrategySelect from "./StrategySelect.vue";
import ApiNewOrder from "../../../api/neworder";
import helpers, { getLocalDate } from "../../../helpers";
import { applyStrategy } from "../services/productionScheduler";
import { changedSlugs } from "../services/productionPlanCalendar";
import { dateToString, parseYMD } from "@/services/datesService";
import StartProductionContext from "./StartProductionContext.vue";

export default {
    name: "StartProductionAction",

    props: {
        agreementLine: {
            type: Object,
            required: true,
        },
        value: {
            type: Boolean,
            default: false
        }
    },

    components: {
        StartProductionContext,
        Sidebar,
        ProductionPlanCalendar,
        StartProductionForm,
        StrategySelect,
    },

    computed: {
        agreementLineId() {
            return this.agreementLine.id;
        },
        confirmedDate() {
            const date = this.agreementLine.confirmedDate
                .split('T')[0]
                .split(' ')[0];
            return parseYMD(date)
        },
        confirmedDateString() {
            return dateToString(this.confirmedDate)
        },
        payload() {
            return (this.form || []).map(row => ({
                department: row.slug,
                dateStart: row.dateStart,
                dateEnd: row.dateEnd,
            }))
        }
    },

    watch: {
        value: {
            immediate: true,
            handler(open) {
                if (open) {
                    this.form = getForm(this.agreementLine.productions);
                }
            }
        }
    },

    methods: {

        async startProduction(closeCallback)
        {
            const isValid = await this.$refs.form.validate();

            if (!isValid) {
                return
            }

            this.saving = true
            return ApiNewOrder.startProduction(this.agreementLineId, { schedule: this.payload })
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('addedToSchedule')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                    if (closeCallback) {
                        closeCallback()
                    }
                })
                .finally(() => {
                    this.saving = false
                })
        },

        onCalendarInput(form) {
            this.updateForm(form);
        },

        // Programmatic changes do not trigger vee-validate errors on their own, so the touched
        // departments are validated explicitly; validating the whole observer would flag empty rows
        updateForm(form) {
            const slugs = changedSlugs(this.form || [], form);
            this.form = form;
            this.$nextTick(() => {
                const providers = (this.$refs.form && this.$refs.form.refs) || {};
                form
                    .filter(row => slugs.includes(row.slug) && row.dateStart && row.dateEnd)
                    .forEach(row => {
                        ['dateStart', 'dateEnd'].forEach(field => {
                            const provider = providers[`${row.slug}.${field}`];
                            provider && provider.validate();
                        });
                    });
            });
        },

        onStrategySelected(strategy) {
            let resolvedStrategy = {};
            try {
                resolvedStrategy = applyStrategy(strategy.strategy, this.confirmedDate)
            } catch (e) {
                EventBus.$emit('message', {
                    type: 'error',
                    content: e
                });
                return
            }

            const form = (this.form || []).map(row => ({ ...row }));
            Object.keys(resolvedStrategy).forEach(key => {
                const [slug, dateField] = key.split('.');
                const row = form.find(r => r.slug === slug);
                if (row) {
                    const value = resolvedStrategy[key].value;
                    row[dateField] = (value && value instanceof Date) ? dateToString(value) : null;
                }
            })
            this.updateForm(form);
        },
    },

    data: () => ({
        form: getForm(),
        saving: false,
    })
}

function getForm(productions = []) {

    const initialValues = productions.reduce((acc, p) => {
        acc[p.departmentSlug] = {
            dateStart: p.dateStart ? getLocalDate(p.dateStart) : null,
            dateEnd: p.dateEnd ? getLocalDate(p.dateEnd) : null,
        };
        return acc;
    }, {});


    return helpers.getDepartments().map(d => ({
        slug: d.slug,
        dateStart: initialValues[d.slug]?.dateStart || null,
        dateEnd: initialValues[d.slug]?.dateEnd || null,
    }));
}

</script>

<style scoped lang="scss">
.start-production {
    padding: 1rem;

    &__calendar {
        margin-top: 1.5rem;
    }
}

@media (max-width: 1199.98px) {
    .context-column {
        margin-top: 1rem;
    }
}
</style>