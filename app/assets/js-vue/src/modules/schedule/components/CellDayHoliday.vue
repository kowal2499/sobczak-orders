<script>
export default {
    name: "CellDayHoliday",
    props: {
        arg: {
            type: Object,
            default: () => ({})
        },
        events: {
            type: Array,
            default: () => ([])
        }
    },
}
</script>

<template>
    <div>
        <div v-for="event in events" class="badge-holiday">
            {{ event.description || $t('schedule.freeDay') }}
        </div>
    </div>
</template>

<style scoped lang="scss">
.badge-holiday {
    display: inline-block;
    max-width: 100%;
    padding: 0.25em 0.4em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: baseline;
    border-radius: 0.25rem;
    background-color: transparent;
    border: 1px solid white;
    color: white;
}
</style>