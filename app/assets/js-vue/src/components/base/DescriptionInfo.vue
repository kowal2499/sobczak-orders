<script>
export default {
    name: "DescriptionInfo",

    props: {
        description: {
            type: String,
            required: true
        }
    },

    computed: {
        targetId() {
            return `description-info-${this._uid}`;
        }
    }
}
</script>

<template>
    <span class="description-info">
        <span :id="targetId" class="description-info__icon" tabindex="0">
            <i class="fa fa-info-circle" />
        </span>
        <!-- the popover is mounted on body, so it never widens the table cell -->
        <b-popover :target="targetId" triggers="hover focus" placement="right" boundary="window">
            <div class="description-info__body" v-html="__mixin_convertNewlinesToHtml(description)" />
        </b-popover>
    </span>
</template>

<style scoped lang="scss">
.description-info__body {
    max-width: 280px;
    font-size: 0.85rem;
    line-height: 1.4;
}
</style>
