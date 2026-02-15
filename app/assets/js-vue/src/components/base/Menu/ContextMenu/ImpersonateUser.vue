<script>
import { defineComponent } from 'vue'
import store from "@/store"
import * as TYPES from "@/store/types"
import { mapGetters } from "vuex"

export default defineComponent({
    name: 'ImpersonateUser',

    components: {},

    mounted() {
        this.fetchData()
    },

    computed: {
        ...mapGetters('user', ['users']),

        usersList() {
            return this.users(true).filter(user => user.id !== this.$user.user.id)
        },

        impersonateBackHref() {
            return '/?_switch_user=_exit'
        }
    },

    methods: {
        fetchData() {
            this.locked = true;
            return store.dispatch(`user/${TYPES.ACTION_USER_FETCH_USERS}`)
                .finally(() => this.locked = false)
        },

        onImpersonate(email) {
            window.location.href = `/?_switch_user=${email}`;
        }
    },

    data: () => ({
        locked: false,
    }),
})
</script>

<template>
    <div>
        <div class="table-responsive" v-if="!locked">
            <div class="alert alert-info">
                {{ $t('_impersonate.description')}}
            </div>
            <table class="table" v-if="usersList.length">
                <tbody>
                    <tr v-for="user in usersList">
                        <td>{{ user.id }}</td>
                        <td>{{ user.firstName }}</td>
                        <td>{{ user.lastName }}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" @click="onImpersonate(user.email)">Przeloguj</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="text-center" v-else>{{ $t('_noData') }}</div>
        </div>
        <div class="text-center" v-else>
            <i class="fa fa-spinner fa-spin fa-2x"></i>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>