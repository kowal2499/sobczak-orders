<template>
    <div>
        <div v-if="isStartDelayed" class="text-center">
            <b-badge variant="danger"><i class="fa fa-exclamation"></i> {{ $t('orders.notStarted') }}</b-badge>
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
        status: Number|String
    },
    watch: {
        status: {
            immediate: true,
            handler() {
                this.isStartDelayed = this.checkStartDelay();
            }
        }
    },
    methods: {
        checkStartDelay() {
            if (parseInt(this.status) !== 0) {
                return false;
            }
            if (!this.dateStart) {
                return false;
            }
            return moment().isAfter(this.dateStart);
        }
    },
    data: () => ({
        isStartDelayed: false
    })
}
</script>

<style scoped>

</style>