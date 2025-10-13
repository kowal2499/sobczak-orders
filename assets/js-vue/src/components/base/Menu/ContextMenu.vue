<script>
import { defineComponent } from 'vue'
import Dropdown from '@/components/base/Dropdown'
import ImpersonateUser from './ContextMenu/ImpersonateUser.vue'
import ImpersonateUserBack from './ContextMenu/ImpersonateUserBack.vue'
import ModalAction from '@/components/base/ModalAction'

export default defineComponent({
    name: "ContextMenu",
    components: {
        Dropdown,
        ImpersonateUser,
        ImpersonateUserBack,
        ModalAction
    },

    computed: {
        userName() {
            const { user } = this.$user || { user: { firstName: null, lastName: null } }
            return [user.firstName, user.lastName].filter(Boolean).join(' ')
        },
        canImpersonate() {
            return this.$user.can('authorization.impersonate') && !this.$user.isImpersonated();
        },
        canImpersonateBack() {
            return this.$user.isImpersonated();
        },
    },
})
</script>

<template>
    <Dropdown :icon-class="canImpersonateBack ? 'fa fa-user-secret' : 'fa fa-user-circle-o'" :btn-text="userName">
        <ModalAction :title="$t('_impersonate.title')" v-if="canImpersonate">
            <ImpersonateUser />
            <template #open-action="{ open }">
                <span class="dropdown-item" @click.prevent="open">
                    <i class="fa fa-user-secret"></i> {{ $t('_impersonate.title') }}
                </span>
            </template>
        </ModalAction>

        <ImpersonateUserBack v-if="canImpersonateBack" />

        <a class="dropdown-item" href="/logout">
            <i class="fa fa-sign-out"></i> {{ $t('_logout')}}
        </a>
    </Dropdown>
</template>

<style lang="scss">
</style>