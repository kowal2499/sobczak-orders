<script>
import CollapsibleCard from '@/components/base/CollapsibleCard'
import RolesNavigation from '../components/RolesNavigation'
import GrantsList from '../components/GrantsList'
import { fetchGrants, fetchModules, fetchRoles } from '../repository/authorizatonRepository'

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
            return fetchRoles().then(response => {
                // return response.data
                return [
                    { id: 1, name: 'Administrator'},
                    { id: 2, name: 'Produkcja Klejenie'},
                    { id: 3, name: 'Produkcja CNC'},
                    { id: 4, name: 'Produkcja Szlifowanie'},
                    { id: 5, name: 'Produkcja Lakierowanie'},
                    { id: 6, name: 'Produkcja Pakowanie'},
                    { id: 7, name: 'Kierownik Produkcji'},
                ]
            });
        },

        onAddRole() {}
    },

    data: () => ({
        isBusy: false,
        roles: [],
        modules: [],
        grants: [],
    })
}
</script>

<template>
    <CollapsibleCard :title="$t('auth.rolesConfigurationTitle')" :locked="true" no-padding>
        <RolesNavigation :roles="roles" #default="{ roleId }">
            <GrantsList :role-id="roleId" :grants="grants" :modules="modules" />
        </RolesNavigation>
    </CollapsibleCard>
</template>

<style scoped lang="scss">

</style>