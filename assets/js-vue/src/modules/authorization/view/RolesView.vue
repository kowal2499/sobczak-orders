<script>
import CollapsibleCard from '@/components/base/CollapsibleCard'
import RolesNavigation from '../components/RolesNavigation'
import GrantsList from '../components/GrantsList'
import {
    fetchGrants,
    fetchModules,
    fetchRoles,
    setGrantRoleValue,
} from '../repository/authorizatonRepository'

export default {
    name: 'RolesView',

    components: {
        CollapsibleCard,
        RolesNavigation,
        GrantsList,
    },

    async mounted() {
        this.isBusy = true
        const [grantsData, rolesData, modulesData] = await Promise.all([
            this.fetchGrants(),
            this.fetchRoles(),
            this.fetchModules(),
        ])
        this.roles = rolesData
        this.modules = modulesData
        this.grants = grantsData
        this.initStore()
        this.isBusy = false
    },

    methods: {
        async fetchGrants() {
            return fetchGrants().then(response => response.data);
        },

        fetchModules() {
            return fetchModules().then(response => response.data);
        },

        fetchRoles() {
            return fetchRoles().then(response => response.data);
        },

        initStore() {
            this.grantsStore = []
            for (const role of this.roles) {
                for (const grant of this.grants) {

                    if (grant.options.length) {
                        for (const option of grant.options) {
                            this.grantsStore.push({
                                roleId: role.id,
                                userId: null,
                                grantId: grant.id,
                                optionSlug: option.optionSlug,
                                value: false
                            })
                        }
                    } else {
                        // Single boolean grant
                        this.grantsStore.push({
                            roleId: role.id,
                            userId: null,
                            grantId: grant.id,
                            optionSlug: null,
                            value: false
                        })
                    }
                }
            }
        },

        onAddRole() {},

        onGrantChange(grant) {
            // Persist change
            setGrantRoleValue(grant)

            // Update local store
            this.grantsStore = this.grantsStore.map(v => {
                if (
                    v.grantId === grant.grantId
                    && (
                        (grant.roleId && grant.roleId === v.roleId)
                        ||
                        (grant.userId && grant.userId === v.userId)
                    )
                    && v.optionSlug === grant.optionSlug
                ) {
                    return { ...v, value: grant.value }
                }
                return { ...v }
            })
            console.log(grant)
        }
    },

    data: () => ({
        isBusy: false,
        roles: [],
        modules: [],
        grants: [],

        grantsStore: []
    })
}
</script>

<template>
    <CollapsibleCard :title="$t('auth.rolesConfigurationTitle')" :locked="true" no-padding>
        <RolesNavigation :roles="roles" #default="{ roleId }">
            <GrantsList
                :role-id="roleId"
                :grants="grants"
                :modules="modules"
                :store="grantsStore"
                @grantChanged="onGrantChange"
            />
        </RolesNavigation>
    </CollapsibleCard>
</template>

<style scoped lang="scss">

</style>