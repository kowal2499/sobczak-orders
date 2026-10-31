<template>
    <div>
        <SectionBlockTitle block :title="pageTitle" :breadcrumbs="breadcrumbs">
            <template #actions>
                <div class="d-flex flex-wrap align-items-center justify-content-end">
                    <b-form-checkbox v-model="showInactiveUsers" switch class="mr-3">
                        {{ $t('user.showInactiveUsers') }}
                    </b-form-checkbox>
                    <ListSearchBar
                        v-model="filterQuery"
                        :placeholder="$t('user.searchPlaceholder')"
                        :search-label="$t('user.search')"
                        :new-label="$t('user.new')"
                        :new-to="{ name: 'user-new' }"
                    />
                </div>
            </template>
        </SectionBlockTitle>

        <SectionBlock class="section-gap">
                <div class="table-responsive" v-if="!locked">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ $t('user.column.firstName') }}</th>
                                <th>{{ $t('user.column.lastName') }}</th>
                                <th>{{ $t('user.column.email') }}</th>
                                <th>{{ $t('user.column.roles') }}</th>
                                <th>{{ $t('user.column.authRoles') }}</th>
                                <th>{{ $t('user.column.active') }}</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="user in filteredUsersList" :key="user.id">
                                <td class="align-middle">{{ user.id }}</td>
                                <td class="align-middle">{{ user.firstName }}</td>
                                <td class="align-middle">{{ user.lastName }}</td>
                                <td class="align-middle">{{ user.email }}</td>
                                <td class="align-middle">
                                    <span v-for="role in user.roles" :key="role" class="badge role-badge mr-1">{{ getRoleName(role) }}</span>
                                </td>
                                <td class="align-middle">
                                    <span v-for="name in (user.authRoles || [])" :key="name" class="badge role-badge role-badge--auth mr-1">{{ name }}</span>
                                </td>
                                <td class="align-middle">
                                    <i v-if="user.active" class="fa fa-check text-success"></i>
                                    <i v-else class="fa fa-times text-danger"></i>
                                </td>
                                <td class="align-middle text-right">
                                    <router-link :to="{ name: 'user-edit', params: { id: user.id } }" class="btn btn-outline-primary btn-sm text-nowrap">
                                        <i class="fa fa-pencil" aria-hidden="true"></i> {{ $t('user.edit') }}
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        </SectionBlock>
    </div>
</template>

<script>
    import helpers from "../../helpers";
    import store from "@/store";
    import * as TYPES from "@/store/types"
    import { mapGetters } from "vuex";
    import ListSearchBar from '@/components/base/ListSearchBar.vue'

    export default {
        name: "UsersList",

        components: { ListSearchBar },

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
.role-badge {
    background-color: rgba(var(--colorPrimaryRgb), 0.12);
    color: var(--colorPrimary);
    font-weight: 500;
}

.role-badge--auth {
    background-color: var(--colorRule);
    color: var(--colorSecondary);
}
</style>