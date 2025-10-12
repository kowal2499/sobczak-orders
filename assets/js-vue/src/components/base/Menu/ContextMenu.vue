<script>
import { defineComponent } from 'vue'
import Dropdown from '@/components/base/Dropdown'
import ModalAction from "@/components/base/ModalAction.vue";

export default defineComponent({
    name: "ContextMenu",
    components: {
        Dropdown,
        ModalAction
    },
    computed: {
        userName() {
            const { user } = this.$user || { user: { firstName: null, lastName: null } }
            return [user.firstName, user.lastName].filter(Boolean).join(' ')
        },

        canImpersonate() {
            return this.$user.can('authorization.impersonate');
        },

        canImpersonateBack() {
            return this.$user.isImpersonated();
        }
    },

    methods: {
        onImpersonate() {

        },
        onImpersonateBack() {
            window.location = '/?_switch_user=_exit'
        }
    }
})
</script>

<template>
    <Dropdown icon-class="fa fa-user-circle-o" :btn-text="userName">

        <a class="dropdown-item" href="/?_switch_user=test10@test.pl" v-if="canImpersonate">
            <i class="fa fa-user-secret"></i> {{ $t('_impersonate')}}
        </a>

<!--        <ModalAction v-if="canImpersonate">-->
<!--            <template #open-action="{ open }">-->
<!--                <span class="dropdown-item" @click.prevent="open">-->
<!--                    <i class="fa fa-user-secret"></i> {{ $t('_impersonate')}}-->
<!--                </span>-->
<!--            </template>-->
<!--        </ModalAction>-->

        <a class="dropdown-item" href="/?_switch_user=_exit"  v-if="canImpersonateBack">
            <i class="fa fa-user"></i> {{ $t('_impersonate_back')}}
        </a>
        <a class="dropdown-item" href="/logout">
            <i class="fa fa-sign-out"></i> {{ $t('_logout')}}
        </a>
    </Dropdown>
</template>

<style scoped lang="scss">
    .dropdown-menu {
        i {
            color: var(--colorPrimary);
            font-size: 12px;
        }
    }
</style>