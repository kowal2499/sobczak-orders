<template>
    <div>
        <SectionBlockTitle block :title="pageTitle" :breadcrumbs="breadcrumbs">
            <template #filters>
                <div class="d-flex flex-wrap justify-content-end align-items-center gap-4">
                    <input
                        v-model="filterQuery"
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Filtruj po imieniu i nazwisku..."
                        style="width: 220px"
                    />
                    <b-form-checkbox v-model="showInactiveUsers" switch>
                        <span class="text-sm">{{ $t('user.showInactiveUsers') }}</span>
                    </b-form-checkbox>
                    <a :href="getRouting().get('view_security_user_new')" class="d-sm-inline-block btn btn-sm btn-success shadow-sm"><i class="fa fa-plus"></i> <span class="pl-1">Nowy użytkownik</span></a>
                </div>
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap">
                <div class="table-responsive has-dropdown" v-if="!locked">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imię</th>
                                <th>Nazwisko</th>
                                <th>Email</th>
                                <th>Rola w systemie</th>
                                <th>Aktywny</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="user in filteredUsersList" :key="user.id">
                                <td>{{ user.id }}</td>
                                <td>{{ user.firstName }}</td>
                                <td>{{ user.lastName }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <span v-for="role in user.roles" :key="role">
                                        <span class="badge badge-info mr-1">{{ getRoleName(role) }}</span>
                                    </span>
                                </td>
                                <td>
                                    <i v-if="user.active" class="fa fa-check text-success"></i>
                                    <i v-else class="fa fa-times text-danger"></i>
                                </td>
                                <td>
                                    <b-dropdown no-caret right boundary="window" toggle-class="btn-sm btn-light icon-only">
                                        <template #button-content>
                                            <i class="fa fa-bars"></i>
                                        </template>
                                        <b-dropdown-item :href="getRouting().get('security_user_edit') + '/' + user.id">
                                            <i class="fa fa-pencil" aria-hidden="true"></i> Edycja
                                        </b-dropdown-item>
                                    </b-dropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        </SectionBlock>
    </div>
</template>

<script>
    import routing from "../../api/routing";
    import helpers from "../../helpers";
    import store from "@/store";
    import * as TYPES from "@/store/types"
    import { mapGetters } from "vuex";

    export default {
        name: "UsersList",

        props: {
            pageTitle: {
                type: String,
                default: ''
            },
            breadcrumbs: {
                type: Array,
                default: () => []
            }
        },

        mounted() {
            this.fetchData()
        },

        computed: {
            ...mapGetters('user', ['users']),

            usersList() {
                return this.users(!this.showInactiveUsers)
            },

            filteredUsersList() {
                if (!this.filterQuery.trim()) return this.usersList
                const q = this.filterQuery.trim().toLowerCase()
                return this.usersList.filter(user =>
                    `${user.firstName} ${user.lastName}`.toLowerCase().includes(q)
                )
            }
        },

        methods: {
            fetchData() {
                this.locked = true;
                return store.dispatch(`user/${TYPES.ACTION_USER_FETCH_USERS}`)
                    .finally(() => this.locked = false)
            },

            getRouting() {
                return routing;
            },

            getRoleName(role) {
                return helpers.getRoleName(role)
            }
        },

        data() {
            return {
                locked: false,
                showInactiveUsers: false,
                filterQuery: '',
            }
        },
    }
</script>

<style scoped>

</style>