<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import NoData from "@/components/base/BaseListing/components/cells/NoData"
import ProductionTaskNotification from "@/components/production/ProductionTaskNotification"
import FactorDisplay from "../FactorDisplay"
import helpers from "@/helpers"
import Roles from "@/definitions/userRoles"

export default {
    name: "DepartmentCell",

    mixins: [cellMixin],

    components: { NoData, ProductionTaskNotification, FactorDisplay },

    computed: {
        production() {
            return this.column ? this.column.resolveValue(this.record) : null;
        },

        statuses() {
            return this.production ? helpers.statusesPerTaskType(this.production.departmentSlug) : [];
        },

        userCanProduction() {
            return this.$user.can(Roles.CAN_PRODUCTION);
        }
    },

    methods: {
        getStatusData(status) {
            return helpers.statuses.find(item => item.value === parseInt(status)) || {};
        },

        updateProduction(newStatus) {
            this.production.status = newStatus;
            this.$emit('statusUpdated', { id: this.production.id, status: newStatus }, this.record.agreementLineId);
        }
    }
}
</script>

<template>
    <div class="task" v-if="production">
        <div class="d-flex flex-column gap-1">
            <b-dropdown
                :text="$t(getStatusData(production.status).name)"
                size="sm"
                class="w-100"
                :class="getStatusData(production.status).className"
                variant="light"
                split-variant=""
                :disabled="disabled"
            >
                <b-dropdown-item
                    v-for="status in statuses"
                    :value="status.value"
                    :key="status.value"
                    :disabled="!userCanProduction"
                    @click="updateProduction(status.value)"
                >{{ $t(status.name) }}</b-dropdown-item>
            </b-dropdown>

            <div class="text-center text-nowrap">
                <span v-if="production.dateStart">{{ production.dateStart | formatDate('YYYY-MM-DD') }}</span>
                <NoData v-else />
            </div>

            <div class="text-center text-nowrap">
                <span v-if="production.dateEnd">{{ production.dateEnd | formatDate('YYYY-MM-DD') }}</span>
                <NoData v-else />
            </div>

            <production-task-notification
                :date-start="production.dateStart"
                :date-end="production.dateEnd"
                :status="production.status"
                :isStartDelayed="production.isStartDelayed"
                :isCompleted="production.isCompleted"
                :date-deadline="record.confirmedDate"
            />

            <FactorDisplay v-if="production.factorBonus" :factor-data="production.factorBonus" />
        </div>
    </div>
</template>

<style lang="scss">
@use "css/helper/variables" as *;

// bootstrap-vue maluje nagłówki na biało przez `.table.b-table > thead > tr > .table-b-table-default`,
// co bije helper `.background-color-primary-light-*` z thClass - stąd dłuższy selektor
table.table.b-table > thead > tr > th {
    &.background-color-primary-light-80 {
        background-color: $primaryLight80;
    }

    &.background-color-primary-light-90 {
        background-color: $primaryLight90;
    }
}

td.prod {
    min-width: 120px;
    background-color: #fbfbfb;
}

// bootstrap podświetla wiersz tłem na <tr>, a nieprzezroczyste tło komórki działu je zasłania.
// Nakładamy tę samą warstwę na własnym tle komórki, zamiast dobierać przyciemniony kolor
tr:hover > td.prod {
    background-image: linear-gradient(
        var(--listing-hover-overlay, rgba(0, 0, 0, 0.075)),
        var(--listing-hover-overlay, rgba(0, 0, 0, 0.075))
    );
}

.b-dropdown, .b-dropdown.show {

    &.dropdown-white button,
    &.dropdown-white button:hover, &.dropdown-white button:active, &.dropdown-white button:focus
    {
        background-color: #E7E7E7;
        color: #333;
    }

    &.dropdown-orange button,
    &.dropdown-orange button:hover, &.dropdown-orange button:active, &.dropdown-orange button:focus
    {
        background-color: #FFA07A;
    }

    &.dropdown-blue button,
    &.dropdown-blue button:hover, &.dropdown-blue button:active, &.dropdown-blue button:focus
    {
        background-color: #87CEFA;
    }

    &.dropdown-green1 button,
    &.dropdown-green1 button:hover, &.dropdown-green1 button:active, &.dropdown-green1 button:focus
    {
        background-color: #8FBC8F;
    }

    &.dropdown-green2 button,
    &.dropdown-green2 button:hover, &.dropdown-green2 button:active, &.dropdown-green2 button:focus
    {
        background-color: #419D78;
        color: #FFFFFF;
    }
}

</style>
