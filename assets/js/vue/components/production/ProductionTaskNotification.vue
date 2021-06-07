<template>
    <div class="flex-column mt-1">
        <div v-if="notStartedNotify" class="text-center">
            <badge :anim="true" variant="danger">
                <template #icon>
                    <i class="fa fa-exclamation-circle"></i>
                </template>
                <template #message>
                    {{ $t('orders.notStarted') }}
                </template>
            </badge>
        </div>
        <div v-if="isStartDelayed" class="text-center">
            <badge variant="info">
                <template #icon>
                    <i class="fa fa-info-circle"></i>
                </template>
                <template #message>
                    {{ $t('orders.startedDelay') }}
                </template>
            </badge>
        </div>
    </div>
</template>

<script>
import moment from "moment";
import Badge from "./Badge";

export default {
    name: "ProductionTaskNotification",
    components: { Badge },
    props: {
        dateStart: String,
        dateEnd: String,
        dateDeadline: String,
        status: Number|String,
        isStartDelayed: Boolean,
        isCompleted: Boolean
    },
    computed: {
        notStartedNotify() {
            if (false === [0, 10].includes(parseInt(this.status))) {
                return false;
            }
            if (!this.dateStart) {
                return false;
            }
            return this.today.isAfter(moment(this.dateStart), 'day');
        },
    },
    data: () => ({
        today: moment(),
    })
}
</script>

<style scoped>
/deep/.badge {
    white-space: break-spaces !important;
    background-color: transparent;
    color: #555;
    border: 1px solid #555;
}
</style>