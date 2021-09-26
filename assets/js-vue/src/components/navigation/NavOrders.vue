<template>
    <div>

        <div class="submenu mx-2 collapse-inner" v-for="element in elements">

            <a :href="element.path" class="d-flex justify-content-between align-items-center" :class="{active: element.active}">
                {{ element.title }}

                <span class="badge badge-light" v-if="getCount(element.statusId) !== null">{{ getCount(element.statusId) }}</span>

            </a>


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

<style scoped>

</style>