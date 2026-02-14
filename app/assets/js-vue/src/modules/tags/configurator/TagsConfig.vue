<template>
    <collapsible-card :title="$t('tags.tags')">
        <template #header>
            <b-button size="sm" variant="success" :disabled="isBusy" @click="showModal({})">
                {{ $t('tags.addNew') }}
            </b-button>
        </template>

        <b-alert show variant="info" v-if="noResults">
            {{ $t('tags.nothingDefined') }}
        </b-alert>
        <b-table v-else responsive striped :fields="fields" :items="tags">
            <template #cell(actions)="data">
                <b-button size="sm" variant="outline-primary" @click="showModal(data.item)">
                    {{ $t('tags.edit') }}
                </b-button>
                <b-button size="sm" variant="outline-danger" @click="showConfirmModal(data.item.id)">
                    {{ $t('tags.delete') }}
                </b-button>
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
    </collapsible-card>
</template>

<script>
import CollapsibleCard from "../../../components/base/CollapsibleCard";
import Modal from "./Modal";
import ConfirmationModal from "../../../components/base/ConfirmationModal";
import {fetchAll, deleteTag} from "../repository";

export default {
    name: "TagsConfig",
    components: {Modal, ConfirmationModal, CollapsibleCard},
    mounted() {
        this.fetch();
    },
    computed: {
        fields() {
            return [
                {
                    key: 'name',
                    label: this.$t('tags.name'),
                    class: 'vertical-align-middle'
                },
                {
                    key: 'module',
                    label: this.$t('tags.module'),
                    class: 'vertical-align-middle'
                },
                {
                    key: 'actions',
                    label: this.$t('tags.actions'),
                    class: 'text-right'
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

<style>
    div#content-wrapper table tbody td.vertical-align-middle {
        vertical-align: middle;
    }
</style>