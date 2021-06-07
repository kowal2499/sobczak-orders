<template>
    <div class="flex-column">
        <div v-if="notStartedNotify" class="text-center">
            <b-badge variant="danger"><i class="fa fa-exclamation"></i> {{ $t('orders.notStarted') }}</b-badge>
        </div>
        <div v-if="startedWithDelayNotify" class="text-center">
            <b-badge variant="warning"><i class="fa fa-exclamation"></i> rozpoczęto z opóźnieniem</b-badge>
        </div>
    </div>
</template>

<script>
import moment from "moment";

export default {
    name: "ProductionTaskNotification",
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
            if (parseInt(this.status) !== 0) {
                return false;
            }

            if (!this.dateStart) {
                return false;
            }

            return this.today.isAfter(
                moment(this.dateStart).utcOffset(0).set({hour: 24, minute: 0, second: 0, millisecond: 0})
            );
        },

        startedWithDelayNotify() {
            return this.isStartDelayed;
        }
    },
    data: () => ({
        today: moment().utcOffset(0).set({hour: 0, minute: 0, second: 0, millisecond: 0}),
    })
}
</script>

<style scoped>

</style>