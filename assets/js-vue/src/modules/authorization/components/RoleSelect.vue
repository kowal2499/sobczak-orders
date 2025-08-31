<script>
import { defineComponent } from 'vue'
import VueSelect from 'vue-select'
import { fetchRolesByUserId, fetchRoles } from '../repository/rolesRepository'

export default defineComponent({
    name: 'RoleSelect',
    props: {
        userId: {
            type: Number,
            required: true
        },

        value: {
            type: Array,
            default: () => ([])
        }
    },

    components: {
        VueSelect
    },

    watch: {
        userId: {
            async handler() {
                if (this.allRoles === undefined) {
                    const { data } = await fetchRoles()
                    this.allRoles = data.map(role => ({ label: role.name, value: role.id, id: role.id }) )
                }

                fetchRolesByUserId(this.userId).then(({data}) => {
                    const allRoleIds = this.allRoles.map(role => role.id)

                    this.innerValue = data
                        .map(role => allRoleIds.includes(role.id) ? role.id : null)
                })
            },
            immediate: true
        }
    },

    computed: {
        innerValue: {
            get() {
                return this.value
            },
            set(v) {
                const valueAsString = JSON.stringify(v)
                if (valueAsString !== JSON.stringify(this.value)) {
                    this.$emit('input', JSON.parse(valueAsString))
                }
            }
        }
    },

    data: () => ({
        allRoles: undefined,
    })

})
</script>

<template>
    <div>
        <vue-select
            v-model="innerValue"
            :options="allRoles"
            multiple
            taggable
            searchable
            :reduce="option => option.value"
        >

        </vue-select>
    </div>
</template>

<style scoped lang="scss">

</style>