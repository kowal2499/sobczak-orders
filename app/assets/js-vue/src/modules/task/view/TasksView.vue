<script>
import proxyValue from '@/mixins/proxyValue'
import TaskItem from '../components/TaskItem.vue'

export default {
    name: 'TasksView',
    components: {
        TaskItem
    }
}
</script>

<template>
    <div>
        <TaskItem />
    </div>
</template>

<style scoped lang="scss">

</style>