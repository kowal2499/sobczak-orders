<template>
    <div class="task-badge" :class="variant">
        <div class="task-icon" :class="{'anim': anim}">
            <slot name="icon"></slot>
        </div>
        <div class="task-message">
            <slot name="message"></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "Badge",
    props: {
        variant: String,
        anim: {
            type: Boolean,
            default: false
        }
    }
}
</script>

<style scoped lang="scss">
    .task-badge {
        font-size: 0.8em;
        display: flex;
        align-items: center;

        border: 1px solid transparent;
        border-radius: 3px;
        padding: 3px;
        line-height: 1em;

        &.danger {
            color: crimson;
            border-color: rgba(220, 20, 60, 0.3);
        }

        &.info {
            color: #0056b3;
            border-color: rgba(0, 86, 179, 0.3);
        }

        .task-icon {
            i {
                font-size: 1.1em;
            }

            &.anim {
                i {
                    animation: pulse-animation 1s infinite;
                }
            }
            width: 20px;
            text-align: center;
        }

    }

    @keyframes pulse-animation {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
        }
    }
</style>