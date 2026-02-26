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
            <font-awesome-icon v-if="icon" :icon="icon" />
            <span v-if="label">{{ label }}</span>
        </div>
        <span class="badge-value"><slot name="value">{{ value }}</slot></span>
    </div>
</template>

<style scoped lang="scss">
    .showcase-badge {
        display: flex;
        position: relative;
        margin-top: 0.5rem;
        //border-top: 1px solid rgba(var(--colorPrimaryRgb), 0.2);
        border-bottom: 1px solid rgba(var(--colorPrimaryRgb), 0.2);

        .badge-label {
            position: absolute;
            top: 0;
            left: 0.3rem;
            transform: translate(0, -50%);
            color: rgba(var(--colorPrimaryRgb), 0.7);
            padding: 0 0.2rem;
            font-size: 0.6rem;
            line-height: 1;
            z-index: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

            svg {
                color: rgba(var(--colorPrimaryRgb), 0.7);
                margin-right: 2px;
            }
        }
        .badge-value {
            flex: 1;
            padding: 0.3rem 0.4rem 0.2rem 0.4rem;
            text-align: left;
            font-size: .85rem;
        }
    }
</style>