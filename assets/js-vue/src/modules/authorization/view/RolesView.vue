<script>
import CollapsibleCard from '@/components/base/CollapsibleCard'
import RolesNavigation from '../components/RolesNavigation'
import GrantsList from '../components/GrantsList'
import {
    fetchGrants,
    fetchModules,
    fetchRoles,
    setGrantRoleValue,
    fetchGrantRoleValues,
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
        const [grantsData, rolesData, modulesData, storeValues] = await Promise.all([
            this.fetchGrants(),
            this.fetchRoles(),
            this.fetchModules(),
            fetchGrantRoleValues()
        ])
        this.roles = rolesData
        this.modules = modulesData
        this.grants = grantsData
        this.initStore(storeValues.data)
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

        initStore(initialValues) {
            this.grantsStore = []
            for (const role of this.roles) {
                for (const grant of this.grants) {

                    const options = grant.options.length ? grant.options : [{ optionSlug: null }];
                    for (const option of options) {
                        const initValue = initialValues.find(v =>
                            v.grant_id === grant.id
                            && v.role_id === role.id
                            && v.option_slug === option.optionSlug
                        )
                        this.grantsStore.push({
                            roleId: role.id,
                            userId: null,
                            grantId: grant.id,
                            optionSlug: option.optionSlug,
                            value: initValue ? initValue.value : false
                        });
                    }
                }
            }
        },

        onAddRole() {},

        onGrantChange(grants) {
            console.log({grants})
            const toPersist = []

            // Update local store
            this.grantsStore = this.grantsStore.map(v => {
                const replacement = grants.find(g =>
                    g.grantId === v.grantId
                    && g.optionSlug === v.optionSlug
                    && g.roleId === v.roleId
                    && g.userId === v.userId
                    && g.value !== v.value
                )
                if (replacement) {
                    toPersist.push(replacement)
                    return replacement
                }
                return v
            })

            // Persist change
            toPersist.forEach(grant => setGrantRoleValue(grant))
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
        <RolesNavigation :roles="roles" :store="grantsStore" #default="{ roleId, contextStore }">
            <GrantsList
                :grants="grants"
                :modules="modules"
                :store="contextStore"
                @grantChanged="onGrantChange"
                :key="roleId"
            />
        </RolesNavigation>
    </CollapsibleCard>
</template>

<style scoped lang="scss">

</style>