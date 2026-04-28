<script>
import CapacityProgressBar from "./CapacityProgressBar.vue"
import Sidebar from "@/components/base/Sidebar.vue"
import CapacitySidebar from "@/modules/schedule/sidebars/CapacitySidebar.vue"

export default {
    name: "WeekCapacityCard",
    components: { CapacityProgressBar, Sidebar, CapacitySidebar },
    props: {
        weekData: {
            type: Object,
            required: true
        }
    },

    computed: {
        canProduction() {
            return this.$user.can(this.$privilages.CAN_PRODUCTION);
        }
    },

    methods: {
        formatDate(dateStr) {
            if (!dateStr) {
                return ''
            }
            const d = new Date(dateStr)
            return d.toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit' })
        }
    },

    data: () => ({
        sidebarOpen: false,
        sidebarTitle: '',
    }),
}
</script>

<template>
    <div class="week-capacity-card border rounded mb-2 p-2">
        <div class="font-weight-bold text-sm mb-1">
            {{ formatDate(weekData.dateStart) }} – {{ formatDate(weekData.dateEnd) }}
        </div>

        <CapacityProgressBar
            class="mb-1"
            :capacity="weekData.capacity"
            :capacity-burned="weekData.capacityBurned"
            :capacity-burned-forecast="weekData.capacityBurnedForecast || 0"
        />

        <div class="text-muted mb-1" style="font-size: 0.75rem">
            {{ weekData.workingDays }} {{ $t('schedule.workingDaysShort') }}
        </div>

        <div v-if="weekData.agreementLines && weekData.agreementLines.length" class="mb-1">
            <button
                class="btn btn-link btn-sm p-0 text-muted"
                style="font-size: 0.75rem"
                @click="sidebarOpen = true"
            >
                {{ $t('schedule.ordersCount', { count: weekData.agreementLines.length }) }}
                <font-awesome-icon icon="arrow-right" />
            </button>
        </div>

        <Sidebar
            v-model="sidebarOpen"
            :title="sidebarTitle"
            sidebar-class="size-100 size-lg-50"
            v-if="canProduction"
        >
            <template #sidebar-content>
                <CapacitySidebar
                    :week-data="weekData"
                    @set-title="sidebarTitle = $event"
                />
            </template>
        </Sidebar>
    </div>
</template>

<style scoped lang="scss">
.week-capacity-card {
    background: #fff;
}
.text-sm {
    font-size: 0.8rem;
}
</style>
