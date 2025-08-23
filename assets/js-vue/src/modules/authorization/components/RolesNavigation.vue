<script>

export default {
    name: 'RolesNavigation',

    props: {
        roles: {
            type: Array,
            default: () => ([]),
        }
    },

    computed: {
        activeRole() {
            return this.roles.find(role => role.id === this.activeRoleId) || null
        }
    },

    methods: {
        onAddRole() {}
    },

    data: () => ({
        activeRoleId: null,
        isBusy: false,
    })
}
</script>

<template>
    <b-row>
        <b-col cols="3" >
            <b-nav pills card vertical class="p-2 background-light">
                <b-button class="w-100 mb-1" size="sm" variant="outline-success" :disabled="isBusy" @click="onAddRole">
                    {{ $t('auth.addNew') }}
                </b-button>
                <b-nav-item
                    v-for="role in roles"
                    :key="role.id"
                    :title="role.name"
                    :active="activeRoleId === role.id"
                    @click="activeRoleId = role.id"
                >
                    {{ role.name }}
                </b-nav-item>
            </b-nav>
        </b-col>

        <b-col>
            <div class="p-3">
                <slot :roleId="activeRoleId" v-if="activeRoleId" />
                <div class="alert alert-info text-center" v-else>
                    {{ $t('auth.selectRole') }}
                </div>
            </div>
        </b-col>
    </b-row>
</template>

<style scoped lang="scss">

</style>