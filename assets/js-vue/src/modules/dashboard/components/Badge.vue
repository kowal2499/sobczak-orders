<template>
    <div class="card shadow py-2 m-2" :class="metric.className">
        <div class="card-body">
            <div class="text-title font-weight-bold text-primary text-uppercase mb-1">
                {{ $t(metric.title) }}
            </div>

            <div class="h5 mb-0 font-weight-bold text-gray-800">
                <font-awesome-icon v-if="metric.busy" icon="spinner" spin/>
                <template v-else>
                    <a v-if="metric.isClickable()" href="#" @click.prevent="$emit('clicked', metric.id)">
                        {{ metric.getValue() }}
                    </a>
                    <span v-else>
                        {{ metric.getValue() }}
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "Badge",
    props: {
        metric: Object,
    }
}
</script>

<style scoped lang="scss">
.card {
    width: 180px;
    min-height: 150px;
    margin-right: 20px;
}

.border-left-success {
    border-left: .25rem solid #1cc88a!important;
}

.border-left-info {
    border-left: .25rem solid #36b9cc!important;
}

.border-left-warning {
    border-left: .25rem solid #f6c23e!important;
}

.border-left-primary {
    border-left: .25rem solid #4e73df!important;
}

.text-title {
    font-size: .7rem;
}
</style>