<script>
import CapacityProgressBar from "./CapacityProgressBar.vue"
import Sidebar from "@/components/base/Sidebar.vue"
import CapacitySidebar from "@/modules/schedule/sidebars/CapacitySidebar.vue"
import SidebarNavbar from "@/components/layout/SidebarNavbar.vue"

export default {
    name: "WeekCapacityCard",
    components: { CapacityProgressBar, Sidebar, CapacitySidebar, SidebarNavbar },
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
        openSidebar() {
            this.openings++
            this.q = ''
            this.sidebarOpen = true
        },
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
        q: '',
        openings: 0,
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

        <div v-if="canProduction && weekData.agreementLines && weekData.agreementLines.length" class="mb-1">
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm week-capacity-card__orders"
                @click="openSidebar"
            >
                {{ $t('schedule.ordersCount', { count: weekData.agreementLines.length }) }}
            </button>
        </div>

        <Sidebar
            v-model="sidebarOpen"
            :title="sidebarTitle"
            sidebar-class="size-100 size-lg-75 size-xxl-50"
            v-if="canProduction"
        >
            <template #sidebar-header-actions>
                <!-- Keyed per opening: the header outlives the lazy body, and openSidebar clears the search. -->
                <SidebarNavbar :key="openings" :show-excel-export-btn="false" @search="q = $event || ''" />
            </template>

            <template #sidebar-content>
                <CapacitySidebar
                    :week-data="weekData"
                    :search="q"
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

// _forms.scss sets .btn-sm padding under #app, so this has to match that specificity
#app .btn.week-capacity-card__orders {
    padding: 0.05rem 0.4rem;
    font-size: 0.75rem;
    line-height: 1.4;
}
.text-sm {
    font-size: 0.8rem;
}
</style>
