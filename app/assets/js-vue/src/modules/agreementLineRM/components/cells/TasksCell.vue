<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"
import Tasks from "../Tasks"
import NoData from "@/components/base/BaseListing/components/cells/NoData"

export default {
    name: "TasksCell",

    mixins: [cellMixin],

    components: { Tasks, NoData },

    computed: {
        tasks() {
            return this.record.tasks || [];
        }
    }
}
</script>

<template>
    <Tasks
        v-if="tasks.length > 0"
        :tasks="tasks"
        :deadline="record.confirmedDate"
        @taskStatusUpdated="payload => $emit('taskStatusUpdated', payload, record.agreementLineId)"
    />
    <NoData v-else :label="$t('orders.notasks')" />
</template>
