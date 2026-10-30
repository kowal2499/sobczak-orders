<script>
import { defineComponent    } from 'vue'
import RoleForm from './RoleForm'
import ModalAction from '@/components/base/ModalAction'
import { createRole } from '../../repository/authorizatonRepository'

export default defineComponent({
    name: 'RoleModalForm',

    components: {
        ModalAction,
        RoleForm
    },

    methods: {
        resetForm() {
            this.form = getForm()
        },

        async onSubmit(closeCallback) {
            const isValid = await this.$refs.form.validate();

            if (!isValid) {
                EventBus.$emit('message', {
                    type: 'error',
                    content: this.$t('_validation.fixFormErrors')
                });
                return
            }

            this.saving = true
            createRole(this.form.name)
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('auth.roleWasAdded')
                    });
                    this.$emit('roleCreated')
                    if (closeCallback) {
                        closeCallback()
                    }
                })
                .catch(error => {
                    const message = error && error.response && error.response.data && error.response.data.error
                        ? error.response.data.error
                        : this.$t('auth.roleSavingError');
                    EventBus.$emit('message', {
                        type: 'error',
                        content: message
                    });
                })
                .finally(() => {
                    this.saving = false
                })
        }
    },

    data: () => ({
        form: {},
        saving: false,
    })
})

function getForm() {
    return {
        name: '',
        description: '',
    }
}

</script>

<template>
    <ValidationObserver ref="form" #default="{ invalid }">
        <ModalAction :title="$t('auth.newRole')" :configuration="{ size: 'md', hideFooter: false }" :show-close="false" @show="resetForm">
            <template #default>
                <RoleForm v-model="form" />
            </template>
            <template #open-action="{ open }">
                <b-button size="sm" variant="primary" @click="open">
                    {{ $t('auth.addNew') }}
                </b-button>
            </template>

            <template #modal-footer="{ close }">
                <div class="form-actions w-100 mt-0">
                    <button type="button" class="btn btn-outline-secondary" :disabled="saving" @click="close">{{ $t('cancel') }}</button>
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="onSubmit(close)">
                        <i v-if="saving" class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                        {{ $t('add') }}
                    </button>
                </div>
            </template>
        </ModalAction>
    </ValidationObserver>
</template>

<style scoped lang="scss">

</style>