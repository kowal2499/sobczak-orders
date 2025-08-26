<script>
import { defineComponent    } from 'vue'
import RoleForm from './RoleForm'
import ModalAction from '@/components/base/ModalAction'
import { createRole } from '../repository/authorizatonRepository'

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
        }
    },

    data: () => ({
        form: {}
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
        <ModalAction :title="$t('auth.newRole')" :configuration="{ size: 'lg', hideFooter: false }" @show="resetForm">
            <template #default>
                <RoleForm v-model="form" />
            </template>
            <template #open-action="{ open }">
                <b-button class="w-100 mb-1" size="sm" variant="outline-success" @click="open">
                    {{ $t('auth.addNew') }}
                </b-button>
            </template>

            <template #modal-footer="{ close }">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-success ml-2" @click="onSubmit(close)">
                        {{ $t('add') }}
                    </button>
                </div>
            </template>
        </ModalAction>
    </ValidationObserver>
</template>

<style scoped lang="scss">

</style>