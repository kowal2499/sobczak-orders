<script>
import ModalAction from "@/components/base/ModalAction"

export default {
    name: "RenameViewModal",

    components: { ModalAction },

    props: {
        value: {
            type: Boolean,
            default: false
        },

        title: {
            type: String,
            default: ''
        }
    },

    watch: {
        value: {
            immediate: true,
            handler(open) {
                if (open) {
                    this.name = this.title;
                }
            }
        }
    },

    methods: {
        close() {
            this.$emit('input', false);
        },

        async submit() {
            const isValid = await this.$refs.form.validate();
            if (!isValid) {
                return;
            }

            this.$emit('renamed', this.name.trim());
            this.close();
        }
    },

    data: () => ({
        name: ''
    })
}
</script>

<template>
    <ValidationObserver ref="form">
        <ModalAction
            :value="value"
            :title="$t('listing.renameView')"
            :configuration="{ hideFooter: false }"
            @input="$emit('input', $event)"
        >
            <template #default>
                <ValidationProvider #default="{ errors }" :rules="{ required: true }" :name="$t('name')">
                    <b-form-group :label="$t('name')" :invalid-feedback="errors.join(' ')">
                        <b-form-input
                            v-model="name"
                            autofocus
                            :state="errors.length === 0 ? null : false"
                            @keyup.enter="submit"
                        />
                    </b-form-group>
                </ValidationProvider>
            </template>

            <template #modal-footer>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-success ml-2" @click="submit">{{ $t('_save') }}</button>
                </div>
            </template>
        </ModalAction>
    </ValidationObserver>
</template>
