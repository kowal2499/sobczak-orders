<template>
    <tr></tr>
</template>

<script>
    import helpers from "../../helpers";

    export default {
        name: "ProductionRowBase",
        props: {
            order: {
                type: Object,
                default: () => {}
            },
            statuses: {
                type: Object,
                default: () => {}
            },
            disabled: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                helpers: helpers,
            }
        },
        methods: {
            userCanProduction() {
                return this.$user.can(this.$privilages.CAN_PRODUCTION);
            },
            getCustomTasks(production) {
                return production.filter(p => { return p.departmentSlug === 'custom_task' ? p : false; })
            },
            getStatusStyle(production) {

                let status = this.helpers.statuses.find(item => item.value === production.status);
                if (status) {
                    return 'background-color: '.concat(status.color);
                }

                return '';
            },
        }
    }
</script>

<style lang="scss">
    td.prod {


        .task {
            label {
                font-size: 0.75rem;
                margin-bottom: 2px;
                color: #aaa;
            }
            select {
                font-size: 0.65rem;
                padding: 5px;
                width: 100%;
            }
        }

        .custom-task {
            width: 120px;
            margin-right: 20px;
        }
    }

    td.prod,
    th.prod {
        background-color: #fbfbfb;
    }
</style>