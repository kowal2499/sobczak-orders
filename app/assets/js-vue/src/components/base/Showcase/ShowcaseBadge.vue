<script>
export default {
    name: "ShowcaseBadge",
    props: {
        icon: String,
        label: String,
        value: String,
        backgroundClass: {
            type: String,
            default: 'color-background-white-100'
        }
    }
}
</script>

<template>
    <div class="showcase-badge">
        <div :class="['badge-label', backgroundClass]">
            <div v-if="icon" class="badge-label-icon">
                <font-awesome-icon :icon="icon" />
            </div>
            <span v-if="label">{{ label }}</span>
        </div>
        <span class="badge-value"><slot name="value">{{ value }}</slot></span>
    </div>
</template>

<style scoped lang="scss">
    .showcase-badge {
        display: flex;
        flex-direction: column;

        .badge-label {
            color: rgba(var(--colorPrimaryRgb), 0.7);
            padding: 0 0.2rem;
            font-size: 0.75rem;
            line-height: 1.2;
            display: flex;
            align-items: flex-start;
            gap: 3px;

            .badge-label-icon {
                flex-shrink: 0;
                margin-top: 1px;

                svg {
                    color: rgba(var(--colorPrimaryRgb), 0.7);
                }
            }
        }
        .badge-value {
            padding: 0.1rem 0.2rem 0.2rem 0.2rem;
            text-align: left;
            font-size: .85rem;
        }
    }
</style>