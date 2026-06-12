<template>
    <div class="default-content">{{ log.content }}</div>
</template>

<script>
export default {
    name: 'DefaultContent',
    props: {
        log: {
            type: Object,
            required: true,
        },
    },
};
</script>

<style lang="scss" scoped>
.default-content {
    font-size: 0.9rem;
    color: #495057;
    line-height: 1.4;
}
</style>
