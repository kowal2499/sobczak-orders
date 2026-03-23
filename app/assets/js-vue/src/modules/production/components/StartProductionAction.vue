<template>
    <ValidationObserver ref="form" #default="{ invalid }">
        <modal-action
            :title="$t('agreement_line_list.startProductionForm.modalTitle')"
            :configuration="{ hideFooter: false, size: 'xl' }"
            :value="value"
            v-on="$listeners"
        >
            <template #open-action="{ open }">
                <slot name="open-action" :open="() => beforeOpen(open)">
                    <a class="dropdown-item p-0"
                       href="#"
                       @click.prevent="beforeOpen(open)"
                    >
                        <i class="fa fa-play" aria-hidden="true"/>
                        {{ $t('startProduction') }}
                    </a>
                </slot>
            </template>

            <template #modal-footer="{ close }">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-success ml-2" @click="startProduction(close)">
                        <i class="fa fa-play mr-2" aria-hidden="true" /> {{ $t('agreement_line_list.startProductionForm.startProduction') }}
                    </button>
                </div>
            </template>

            <template #default="{ close }">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="d-flex flex-column gap-3">
                            <StrategySelect @strategySelected="onStrategySelected" />
                            <hr />
                            <StartProductionForm
                                v-model="form"
                                :confirmedDate="confirmedDate"
                            />
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <collapsible-card :title="$t('orders.orderProcessing')">
                            <agreement-line-widget :agreement-line="agreementLine" disable-edit />
                        </collapsible-card>

                        <collapsible-card :title="$t('product')" v-if="agreementLine?.Product">
                            <product-widget :product="agreementLine.Product" disable-edit />
                        </collapsible-card>

                        <collapsible-card :title="$t('orders.orderDetails')" v-if="agreementLine?.Agreement" collapsed-on-start>
                            <agreement-widget :agreement="agreementLine.Agreement" disable-edit />
                        </collapsible-card>
                    </div>
                </div>
            </template>
        </modal-action>
    </ValidationObserver>
</template>

<script>
import ModalAction from "../../../components/base/ModalAction.vue";
import StartProductionForm from "./StartProductionForm.vue";
import StrategySelect from "./StrategySelect.vue";
import ApiNewOrder from "../../../api/neworder";
import helpers from "../../../helpers";
import { applyStrategy } from "../services/productionScheduler";
import { dateToString, parseYMD } from "@/services/datesService";
import CollapsibleCard from "@/components/base/CollapsibleCard.vue";
import AgreementWidget from "@/components/orders/single/AgreementWidget.vue";
import AgreementLineWidget from "@/components/orders/single/AgreementLineWidget.vue";
import ProductWidget from "@/components/orders/single/ProductWidget.vue";

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
        ProductWidget, CollapsibleCard, AgreementWidget, AgreementLineWidget,
        ModalAction,
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
        payload() {
            return (this.form || []).map(row => ({
                department: row.slug,
                dateStart: row.dateStart,
                dateEnd: row.dateEnd,
            }))
        }
    },

    methods: {
        beforeOpen(callback) {
            this.form = getForm();
            callback && callback();
        },

        async startProduction(closeCallback)
        {
            const isValid = await this.$refs.form.validate();

            if (!isValid) {
                return
            }

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

            EventBus.$emit('message', {
                type: 'success',
                content: this.$t('production.datesUpdatedByStrategy', {name: this.$t(strategy.name)})
            });

            Object.keys(resolvedStrategy).forEach(key => {
                const [slug, dateField] = key.split('.');
                const idx = (this.form || []).findIndex(r => r.slug === slug);
                if (idx !== -1) {
                    const value = resolvedStrategy[key].value;
                    this.form[idx][dateField] = (value && value instanceof Date)
                        ? dateToString(value)
                        : null;

                }
            })
        },
    },

    data: () => ({
        form: getForm()
    })
}

function getForm() {
    return helpers.getDepartments().map(d => ({
        slug: d.slug,
        dateStart: null,
        dateEnd: null,
    }));
}

</script>

<style scoped lang="scss">

</style>