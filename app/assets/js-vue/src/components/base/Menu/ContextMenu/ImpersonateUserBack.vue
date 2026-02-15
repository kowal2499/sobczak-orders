<script>
import { defineComponent } from 'vue'
export default defineComponent({
    name: 'ImpersonateUserBack',
    computed: {
        impersonateBackHref() {
            return '/?_switch_user=_exit'
        }
    }
})
</script>

<template>
    <a class="dropdown-item" href="/?_switch_user=_exit">
        <i class="fa fa-user"></i> {{ $t('_impersonate.titleBack')}}
    </a>
</template>

<style scoped lang="scss">

</style>