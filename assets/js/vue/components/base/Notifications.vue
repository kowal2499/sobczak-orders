<template>
    <div class="notifications">

        <transition-group name="fade">
            <div v-for="(message, index) in messages" class="notification alert alert-dismissible" :class="getClass(message)" :key="message.key">
                {{ message.content }}
                <button type="button" class="close" aria-label="Close" @click.prevent="remove(message.key)">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </transition-group>

    </div>
</template>

<script>
    export default {
        name: "Notifications",

        props: {
            bag: {
                type: Array,
                default: () => []
            }
        },

        data() {
            return {
                lastIndex: 0,

                messages: []
            }
        },

        created() {
            Event.$on('message', (data) => {
                this.addNewMessage(data)
            });

            this.bag.forEach(item => {
                this.addNewMessage(item)
            })
        },

        methods: {
            getClass(message) {
                return message.type === 'success' ? 'alert-success': 'alert-danger';
            },

            remove(key) {
                let index = this.messages.findIndex(i => { return key === i.key; });
                if (index !== -1) {
                    this.messages.splice(index, 1);
                }
            },

            addNewMessage(data) {
                this.messages.push(
                    {
                        type: data.type,
                        content: data.content,
                        key: this.lastIndex
                    }
                );
                let toRemove = this.lastIndex;
                window.setTimeout(() => { this.remove(toRemove); }, 4000);
                this.lastIndex += 1;
            }
        }
    }
</script>

<style lang="scss" scoped>

    .notifications {
        position: fixed;
        right: 0;
        bottom: 0;
        width: 350px;
        margin: 20px;
        z-index: 1000;

        .notification {
            padding: 20px;
            padding-right: 35px;

            button {
                outline: none;
            }
        }

        .fade-enter-active,
        .fade-leave-active {
            transition: opacity .7s;
        }

        .fade-enter,
        .fade-leave-to {
            opacity: 0;
        }
    }

</style>