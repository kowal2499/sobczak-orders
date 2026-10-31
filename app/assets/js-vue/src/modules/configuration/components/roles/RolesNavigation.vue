<script>
import { mapGetters } from "vuex";

export default {
    name: 'RolesNavigation',

    computed: {
        ...mapGetters('auth', ['allRoles']),
    },

    data: () => ({
        activeRoleId: null,
    })
}
</script>

<template>
    <b-row>
        <b-col cols="3" class="roles-nav">
            <!-- stays in view while the long grant list scrolls, so roles can be switched anywhere -->
            <b-nav pills vertical class="roles-nav__list">
                <b-nav-item
                    v-for="role in allRoles"
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
            <slot :roleId="activeRoleId" v-if="activeRoleId" />
            <p class="roles-empty" v-else>
                {{ $t('auth.selectRole') }}
            </p>
        </b-col>
    </b-row>
</template>

<style scoped lang="scss">
.roles-nav {
    border-right: 1px solid var(--colorRule);

    &__list {
        position: sticky;
        top: 1.25rem;
    }
}

.roles-empty {
    margin: 0;
    padding: 0.5rem 0;
    color: var(--colorInkMuted);
}
</style>
