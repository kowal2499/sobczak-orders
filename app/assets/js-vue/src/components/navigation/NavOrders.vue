<template>
    <div>

        <div class="submenu collapse-inner" v-for="element in elements">

            <router-link :to="element.to" class="d-flex justify-content-between align-items-center" :class="{active: element.active}">
                {{ element.title }}

                <span class="badge badge-light" v-if="getCount(element.statusId) !== null">{{ getCount(element.statusId) }}</span>
            </router-link>

        </div>

        <div class="submenu collapse-inner new-order-wrapper" v-if="userCanAddOrder">
            <router-link :to="{ name: 'order-new' }" class="new-order btn btn-primary btn-sm">
                <i class="fa fa-plus mr-2" aria-hidden="true"></i>{{ $t('newOrder') }}
            </router-link>
        </div>

    </div>
</template>

<script>
    import Api from '../../api/widgets';

    const STATUS_ARCHIVED = 20;

    export default {
        name: 'NavOrders',

        props: ['elements'],

        data() {
            return {
                summary: [],
            }
        },

        computed: {
            userCanAddOrder() {
                return this.$user.can(this.$privilages.CAN_ORDERS_ADD);
            },
        },

        mounted() {
            EventBus.$on('statusUpdated', this.fetchData);

            this.fetchData();
        },

        methods: {
            fetchData() {
                Api.ordersCount()
                .then(({data}) => {
                    this.summary = data;
                })
                .catch((data) => {
                    for (let msg of data.response.data) {
                        EventBus.$emit('message', {
                            type: 'error',
                            content: msg
                        });
                    }
                })
                .finally(() => {})
            },

            getCount(statusId) {
                if (this.summary.length === 0 || !statusId || statusId === STATUS_ARCHIVED) {
                    return null;
                }

                let item = this.summary.find(i => statusId === i.statusId);

                if (item) {
                    return item.ordersCount;
                }

                return null;
            }
        }
    }
</script>

<style scoped lang="scss">
.new-order-wrapper {
    border-top: 1px solid #d6d9e0;
    padding: 0.6rem 0.7rem;
}

// nadpisuje bazowe style linków submenu, żeby akcja wyglądała jak przycisk
.new-order.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0.35rem 0.6rem !important;
    color: #fff !important;
    font-size: 0.8rem;
    white-space: nowrap;

    &:hover,
    &:focus {
        color: #fff !important;
        font-weight: normal;
    }
}
</style>