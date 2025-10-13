<script>
import CollapsibleCard from '@/components/base/CollapsibleCard'
import RolesNavigation from '../components/roles/RolesNavigation'
import GrantsList from '../components/GrantsList'
import GrantValue from '../components/GrantValue'
import RoleModalForm from '../components/forms/RoleModalForm'
import { mapGetters } from 'vuex'
import { isEqual } from 'lodash'
import { fetchGrantRoleValues } from '../repository/rolesRepository'
import { setGrantRoleValue } from '../repository/authorizatonRepository'

export default {
    name: 'RolesView',

    components: {
        GrantValue,
        RoleModalForm,
        CollapsibleCard,
        RolesNavigation,
        GrantsList,
    },

    computed: {
        ...mapGetters('auth', ['allGrants', 'allRoles', 'allModules']),
    },

    mounted() {
        this.reset()
    },

    methods: {
        async reset() {
            this.isBusy = true
            const grantRoleValues = await fetchGrantRoleValues()
                .then(({data}) => data)

            const valuesPerRoleAndGrant = {}

            // first set all grants to false
            for (let role of this.allRoles) {
                valuesPerRoleAndGrant[role.id] = this.allGrants.reduce((prev, current) => {
                    prev[current.id] = current.options.length
                        ? [
                            ...current.options.map(option => ({
                                grant_id: current.id,
                                grant_option_slug: option.optionSlug,
                                role_id: role.id,
                                user_id: null,
                                value: false,
                            }))
                        ]
                        : [{
                            grant_id: current.id,
                            grant_option_slug: null,
                            role_id: role.id,
                            user_id: null,
                            value: false,
                        }]
                    return prev
                }, {})
            }

            grantRoleValues.forEach(({grant_id, role_id, grant_option_slug, value}) => {
                const idx = valuesPerRoleAndGrant[role_id][grant_id].findIndex(option => option.grant_option_slug === grant_option_slug)
                if (idx !== -1) {
                    valuesPerRoleAndGrant[role_id][grant_id][idx].value = value
                }
            })

            this.valuesPerRoleAndGrant = valuesPerRoleAndGrant
            this.isBusy = false
        },

        setValuesPerGrant(roleId, grantId, grantsValue) {
            if (isEqual(grantsValue, this.valuesPerRoleAndGrant[roleId][grantId])) {
                return
            }
            this.valuesPerRoleAndGrant = {
                ...this.valuesPerRoleAndGrant,
                [roleId]: {
                    ...this.valuesPerRoleAndGrant[roleId],
                    [grantId]: grantsValue
                }
            }

            this.save(roleId, grantsValue)
        },

        save(roleId, grantValues) {
            return setGrantRoleValue(roleId, grantValues)
                .catch(() => {
                    EventBus.$emit('message', {
                        type: 'error',
                        content: 'Błąd zapisu danych'
                    });
                })
        }
    },

    data: () => ({
        isBusy: false,
        valuesPerRoleAndGrant: {}
    })
}
</script>

<template>
    <CollapsibleCard :title="$t('auth.rolesConfigurationTitle')" :locked="true" no-padding>
        <RolesNavigation>
            <template #actions>
                <RoleModalForm @roleCreated="reset" />
            </template>
            <template #default="{ roleId }">
                <GrantsList #grantValue="{ grant }">
                    <GrantValue
                        :grant="grant"
                        :role-id="roleId"
                        :value="valuesPerRoleAndGrant[roleId][grant.id]"
                        @input="value => setValuesPerGrant(roleId, grant.id, value)"
                        class="my-2"
                    />
                </GrantsList>
            </template>
        </RolesNavigation>
    </CollapsibleCard>
</template>

<style scoped lang="scss">

</style>