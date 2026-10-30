<template>
    <SectionBlock class="section-gap">
        <p v-if="noResults" class="text-muted mb-0">
            {{ $t('tags.nothingDefined') }}
        </p>
        <b-table v-else responsive :fields="fields" :items="tags" class="mb-0">
            <template #cell(name)="data">
                <span class="tag-preview">
                    <b-icon v-if="data.item.icon" :icon="data.item.icon" :style="{ color: data.item.color }" aria-hidden="true" />
                </span>
                {{ data.item.name }}
            </template>
            <template #cell(actions)="data">
                <div class="tag-actions">
                    <b-button size="sm" variant="outline-primary" @click="showModal(data.item)">
                        <i class="fa fa-pencil" aria-hidden="true"></i> {{ $t('tags.edit') }}
                    </b-button>
                    <b-button size="sm" variant="outline-danger" @click="showConfirmModal(data.item.id)">
                        {{ $t('tags.delete') }}
                    </b-button>
                </div>
            </template>
        </b-table>

        <modal v-bind="modal" @close="closeModal"/>
        <confirmation-modal
            :show="deleteModal.visible"
            @closeModal="deleteModal.visible=false"
            @answerYes="deleteTag"
            :busy="false"
        >
            {{ $t('tags.confirmRemove') }}
        </confirmation-modal>
    </SectionBlock>
</template>

<script>
import Modal from "./Modal";
import ConfirmationModal from "../../../components/base/ConfirmationModal";
import {fetchAll, deleteTag} from "../repository";

export default {
    name: "TagsConfig",
    components: {Modal, ConfirmationModal},
    mounted() {
        this.fetch();
    },
    computed: {
        fields() {
            return [
                {
                    key: 'name',
                    label: this.$t('tags.name'),
                    class: 'align-middle'
                },
                {
                    key: 'actions',
                    label: '',
                    class: 'align-middle text-right'
                }
            ]
        }
    },
    methods: {
        fetch() {
            this.isBusy = true;
            fetchAll()
                .then(({data}) => {
                    this.tags = data;
                    this.noResults = this.tags.length === 0
                })
                .finally(() => this.isBusy = false)
        },
        showModal(definitionData) {
            this.modal.visible = true;
            this.modal.definitionData = definitionData;
        },
        closeModal(eventData) {
            this.modal.visible = false;
            if (eventData === null) {

            } else if (!eventData.id) {
                // new tag was added
                this.fetch();
            } else {
                // update existing tag
                this.tags = this.tags.map(tag => {
                    if (tag.id === eventData.id) {
                        return eventData;
                    }
                    return tag;
                })
            }
        },
        showConfirmModal(definitionId) {
            this.deleteModal.visible = true;
            this.deleteModal.context = definitionId;
        },
        deleteTag() {
            this.isBusy = true;
            deleteTag(this.deleteModal.context)
                .finally(() => {
                    this.deleteModal.visible = false;
                    this.fetch();
                })
        }
    },
    data: () => ({
        isBusy: false,
        noResults: false,
        tags: [],

        modal: {
            visible: false,
            definitionData: {}
        },

        deleteModal: {
            visible: false,
            context: null
        }
    })
}
</script>

<style scoped>
.tag-preview {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.5rem;
    margin-right: 0.5rem;
    border: 1px solid var(--colorRule);
    border-radius: 0.25rem;
    vertical-align: middle;
}

.tag-actions {
    display: inline-flex;
    gap: 0.35rem;
}
</style>