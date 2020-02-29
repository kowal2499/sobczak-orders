<template>

    <div class="card shadow mr-3 my-1" :class="borderClass" :style="getWidth">
        <div class="card-body">
            <slot name="body">
                <div class="align-items-center">
                    <div class="text-title font-weight-bold text-primary text-uppercase mb-1">{{ title }}</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ value }}</div>
                </div>
            </slot>
        </div>
    </div>

</template>

<script>
    export default {
        name: "Badge",

        props: ['borderClass', 'textClass', 'width', 'title', 'value'],

        computed: {
            getWidth() {
                if (this.width) {
                    return `width: ${this.width}`;
                } else {
                    return `width: auto;`
                }
            }
        }
    }
</script>

<style scoped lang="scss">

    .card {
        display: inline-block;
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