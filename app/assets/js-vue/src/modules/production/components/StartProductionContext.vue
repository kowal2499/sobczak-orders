<template>
    <SectionBlock class="start-context">
        <AgreementLineHeading
            :agreement-line-id="summary.agreementLineId"
            :order-number="displayNumber"
            :customer-name="summary.customerName || ''"
            :product-name="summary.productName || ''"
            :status="summary.status"
        >
            <template v-if="summary.issuedBy" #aside>
                <Avatar :name="summary.issuedBy" class="start-context__avatar" />
            </template>
        </AgreementLineHeading>

        <div class="start-context__dates">
            <span v-for="date in dates" :key="date.key" class="start-context__date">
                <span class="start-context__label">{{ $t(`production.startContext.${date.key}`) }}</span>
                <span v-if="date.value">{{ date.value }}</span>
                <span v-else class="text-muted">{{ $t('production.startContext.noData') }}</span>
            </span>
        </div>

        <div v-if="summary.description" class="start-context__description">
            <button
                type="button"
                class="btn btn-link btn-sm p-0 start-context__toggle"
                :aria-expanded="String(descriptionOpen)"
                @click="descriptionOpen = !descriptionOpen"
            >
                <i class="fa" :class="descriptionOpen ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true" />
                {{ $t(descriptionOpen ? 'production.startContext.hideDescription' : 'production.startContext.showDescription') }}
            </button>
            <div v-if="descriptionOpen" class="start-context__description-text">{{ summary.description }}</div>
        </div>
    </SectionBlock>
</template>

<script>
import moment from 'moment'
import AgreementLineHeading from '@/components/base/Showcase/AgreementLineHeading.vue'
import Avatar from '@/components/base/Avatar.vue'
import { orderDisplayNumber } from '@/helpers'
import { agreementLineSummary } from '../services/agreementLineSummary'

const DATE_FORMAT = 'YYYY-MM-DD'

export default {
    name: 'StartProductionContext',

    components: { AgreementLineHeading, Avatar },

    props: {
        agreementLine: { type: Object, required: true },
    },

    data: () => ({
        descriptionOpen: false,
    }),

    computed: {
        summary() {
            return agreementLineSummary(this.agreementLine)
        },

        displayNumber() {
            return orderDisplayNumber(this.summary.orderNumber, this.summary.internalNumber)
        },

        dates() {
            const format = value => (value ? moment(value).format(DATE_FORMAT) : null)
            return [
                { key: 'deliveryDate', value: format(this.summary.confirmedDate) },
                { key: 'receiveDate', value: format(this.summary.createDate) },
            ]
        },
    },
}
</script>

<style scoped lang="scss">
.start-context__avatar {
    width: 22px;
    height: 22px;
    font-size: 0.6rem;
}

.start-context__dates {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem 1.25rem;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: var(--colorInkStrong);
}

.start-context__label {
    margin-right: 0.35rem;
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--colorInkMuted);
}

.start-context__description {
    margin-top: 0.35rem;
    font-size: 0.9rem;
}

.start-context__toggle {
    font-size: inherit;

    .fa {
        margin-right: 0.25rem;
    }
}

.start-context__description-text {
    margin-top: 0.35rem;
    white-space: pre-line;
    overflow-wrap: anywhere;
}
</style>
