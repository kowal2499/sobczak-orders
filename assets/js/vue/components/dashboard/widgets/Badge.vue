<template>

    <div class="card shadow h-100 py-2" :class="borderClass">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col">
                    <div class="text-title font-weight-bold text-primary text-uppercase mb-1">{{ title }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ value }}</div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        name: "Badge",

        props: ['borderClass', 'textClass', 'title', 'value']
    }
</script>

<style scoped lang="scss">

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