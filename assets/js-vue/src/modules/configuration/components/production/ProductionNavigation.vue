<script>
export default {
    name: "ProductionNavigation",
    computed: {
        items() {
            return [
                { name: this.$t('config.production.calendarTitle'), id: 'working-days-calendar' },
                { name: this.$t('config.production.capacityTitle'), id: 'daily-capacity' },
            ]
        }
    },
    data: () => ({
        activeItemId: 'working-days-calendar',
        // activeItemId: 'daily-capacity',
    })
}
</script>

<template>
    <b-row>
        <b-col md="3">
            <b-nav pills card vertical class="p-2 background-light h-100">
                <b-nav-item
                    v-for="item in items"
                    :key="item.id"
                    :title="item.name"
                    :active="activeItemId === item.id"
                    @click="activeItemId = item.id"
                >
                    {{ item.name }}
                </b-nav-item>
            </b-nav>
        </b-col>
        <b-col md="9">
            <div class="p-3">
                <slot :name="activeItemId" />
            </div>
            <div class="p-3">
                <slot name="footer" />
            </div>
        </b-col>
    </b-row>
</template>

<style scoped lang="scss">

</style>