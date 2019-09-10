<template>
    <div>
        <table class="table table-bordered">
            <tr v-if="value.name">
                <th>Nazwa</th>
                <td>{{ value.name }}</td>
            </tr>
            <tr v-if="value.factor" >
                <th>Współczynnik</th>
                <td>{{ value.factor }}</td>
            </tr>
            <tr v-if="value.description">
                <th>Opis</th>
                <td>{{ value.description }}</td>
            </tr>
        </table>
        <a :href="getEditLink" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mb-3" v-if="userCan()">
            <i class="fa fa-pencil" aria-hidden="true"></i>
            Edytuj
        </a>
    </div>
</template>

<script>

    import routing from "../../../api/routing";

    export default {
        name: "ProductWidget",
        props: ['value'],

        computed: {
            getEditLink() {
                return routing.get('products_edit').concat('/' + String(this.value.id));
            }
        },

        methods: {
            userCan() {
                return this.$user.can(this.$privilages.CAN_PRODUCTS);
            }
        }

    }
</script>

<style scoped>

</style>