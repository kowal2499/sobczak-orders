<script>

import { fetchGrants, fetchModules } from '../repository/authorizatonRepository'

export default {
    name: 'RolesView',

    async mounted() {
        this.isBusy = true
        const [grantsData, modulesData] = await Promise.all([this.fetchGrants(), this.fetchModules()])
        console.log({ grantsData, modulesData })
        this.isBusy = false
    },

    methods: {
        async fetchGrants() {
            return fetchGrants().then(response => response.data);
        },

        fetchModules() {
            return fetchModules().then(response => response.data);
        }
    },

    data: () => ({
        isBusy: false
    })
}
</script>

<template>
    <div>komponent zarządzania rolami</div>
</template>

<style scoped lang="scss">

</style>