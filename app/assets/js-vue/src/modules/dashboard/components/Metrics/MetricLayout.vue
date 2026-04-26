<template>
    <div class="card shadow">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div class="text-title font-weight-bold text-primary text-uppercase mb-1">
                    <slot name="title" />
                </div>
                <div class="text-right" :id="popoverTargetId" v-if="$slots.description">
                    <font-awesome-icon icon="info-circle" class="text-primary" />
                </div>
            </div>

            <div class="h5 mb-0 font-weight-bold text-gray-800">
                <font-awesome-icon v-if="isBusy" icon="spinner" spin/>
                <slot v-else />
            </div>

            <b-popover :target=popoverTargetId triggers="hover">
                <template #title><span class="text-primary"><slot name="title" /></span></template>
                <slot name="description" />
            </b-popover>
        </div>
    </div>
</template>

<script>
export default {
    name: "BaseBadge",
    props: {
        isBusy: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        popoverTargetId() {
            return `popover-target-${this._uid}`
        }
    }
}
</script>

<style scoped lang="scss">
:deep(strong) {
    color: var(--colorPrimary)
}

.card {
    width: 100%;
    min-height: 150px;
    margin-right: 20px;
    margin-bottom: 2rem;
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