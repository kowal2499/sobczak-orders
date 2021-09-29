<template>
    <div class="card shadow" :class="metric.className">
        <div class="card-body">
            <div class="text-title font-weight-bold text-primary text-uppercase mb-1">
                <slot name="title">
                    {{ $t(metric.title) }}
                </slot>
            </div>

            <div class="h5 mb-0 font-weight-bold text-gray-800">
                <font-awesome-icon v-if="metric.busy" icon="spinner" spin/>
                <slot v-else>
                    {{ metric.getValue() }}
                </slot>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "BaseBadge",
    props: {
        metric: {
            type: Object,
            required: true
        },
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