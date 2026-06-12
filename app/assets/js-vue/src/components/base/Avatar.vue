<template>
    <div class="avatar" :title="name">{{ initials }}</div>
</template>

<script>
export default {
    name: 'Avatar',
    props: {
        name: {
            type: String,
            default: '',
        },
    },
    computed: {
        initials() {
            if (!this.name) {
                return '?';
            }
            const parts = this.name.trim().split(/\s+/);
            const first = parts[0]?.[0] ?? '';
            const second = parts.length > 1 ? parts[parts.length - 1][0] : '';
            return (first + second).toUpperCase() || '?';
        },
    },
};
</script>

<style lang="scss" scoped>
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--colorPrimary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
    user-select: none;
}
</style>
