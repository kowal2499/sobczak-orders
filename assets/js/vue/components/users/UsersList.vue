<template>

    <div class="row">
        <div class="col-sm-12">
            <collapsible-card
                :title="'Użytkownicy systemu'"
                :locked="locked"
            >

                <div class="row">
                    <div class="col">
                        <a :href="getRouting().get('view_security_user_new')" class="d-sm-inline-block btn btn-sm btn-success shadow-sm mb-3 float-right"><i class="fa fa-plus"></i> <span class="pl-1">Nowy użytkownik</span></a>
                    </div>
                </div>

                <div class="table-responsive has-dropdown" v-if="!locked">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imię</th>
                                <th>Nazwisko</th>
                                <th>Email</th>
                                <th>Rola w systemie</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="user in users">
                                <td>{{ user.id }}</td>
                                <td>{{ user.firstName }}</td>
                                <td>{{ user.lastName }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <span v-for="role in user.roles">
                                        <span class="badge badge-info mr-1">{{ getRoleName(role) }}</span>
                                    </span>
                                </td>
                                <td>
                                    <dropdown class="icon-only">
                                        <template>
                                            <a class="dropdown-item" :href="getRouting().get('security_user_edit') + '/' + user.id">
                                                <i class="fa fa-pencil" aria-hidden="true"></i> Edycja
                                            </a>
                                        </template>
                                    </dropdown>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            </collapsible-card>
        </div>
    </div>


</template>

<script>

    import CollapsibleCard from "../base/CollapsibleCard";
    import Dropdown from "../base/Dropdown";
    import UsersApi from "../../api/users";
    import routing from "../../api/routing";
    import helpers from "../../helpers";

    export default {
        components: { CollapsibleCard, Dropdown },

        name: "UsersList",

        data() {
            return {
                locked: false,
                users: []
            }
        },

        mounted() {
            this.locked = true;
            UsersApi.fetchUsers()
                .then(({data}) => {
                    this.users = data;
                })
                .catch(() => {})
                .finally(() => {
                    this.locked = false;
                })
        },

        methods: {
            getRouting() {
                return routing;
            },

            getRoleName(role) {
                return helpers.getRoleName(role)
            }
        }
    }
</script>

<style scoped>

</style>