<script>
import { defineComponent } from 'vue'
import VueSelect from 'vue-select'
import { fetchRolesByUserId } from '../../repository/rolesRepository'
import { mapGetters } from 'vuex'

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
        ...mapGetters('auth', ['allRoles']),

        roleOptions() {
            return this.allRoles.map(role => ({
                label: role.name,
                value: role.id,
                id: role.id
            }))
        },

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

})
</script>

<template>
    <div>
        <vue-select
            v-model="innerValue"
            :options="roleOptions"
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