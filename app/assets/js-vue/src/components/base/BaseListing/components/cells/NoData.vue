<script>
export default {
    name: "NoData",

    props: {
        label: {
            type: String,
            default: null
        }
    }
}
</script>

<template>
    <span class="text-muted text-sm text-nowrap opacity-75">
        <i class="fa fa-ban mr-1" /> {{ label || $t('noData') }}
    </span>
</template>
