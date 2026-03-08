<template>
    <div class="attachment-subsection">
        <vue2dropzone
            ref="dropzone"
            id="agreement-dropzone"
            :options="dropzoneOptions"
            @vdropzone-file-added="onFileAdded"
            @vdropzone-removed-file="onFileRemoved"
            @vdropzone-queue-complete="$emit('vdropzone-queue-complete')"
            @vdropzone-error="(file, message, xhr) => $emit('vdropzone-error', file, message, xhr)"
        />
    </div>
</template>

<script>
import Vue2dropzone from 'vue2-dropzone';
import 'vue2-dropzone/dist/vue2Dropzone.min.css';

export default {
    name: 'AttachmentForm',

    components: { Vue2dropzone },

    data() {
        return {
            removedIds: [],
            dropzoneOptions: {
                url: '/',
                thumbnailWidth: 150,
                maxFilesize: 50,
                acceptedFiles: 'image/*,application/pdf,.txt,.cs',
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: true,
                parallelUploads: 20,
                dictDefaultMessage: '<i class="fa fa-cloud-upload fa-2x"></i><br>Przeciągnij pliki tutaj lub kliknij, aby wybrać</small>',
                dictRemoveFile: 'Usuń',
                dictCancelUpload: 'Anuluj',
            }
        };
    },

    methods: {
        getQueuedFiles() {
            return this.$refs.dropzone.getQueuedFiles();
        },

        getRemovedIds() {
            return this.removedIds;
        },

        processQueue(url, appendFormValues) {
            this.$refs.dropzone.setOption('url', url);
            this.$refs.dropzone.$on('vdropzone-sending', (file, xhr, formData) => {
                appendFormValues(formData);
            });
            this.$refs.dropzone.processQueue();
        },

        loadExistingFiles(attachments) {
            this.removedIds = [];
            attachments.forEach(attachment => {
                const mockFile = {
                    id: attachment.id,
                    name: attachment.originalName + '.' + attachment.extension,
                    size: 0,
                    type: 'existing',
                    status: 'existing',
                    accepted: true,
                };
                this.$refs.dropzone.manuallyAddFile(mockFile, attachment.thumbnail || attachment.url);
            });
        },

        onFileAdded() {
            this.$emit('files-changed', this.getQueuedFiles());
        },

        onFileRemoved(file) {
            if (file.id) {
                this.removedIds.push(file.id);
            }
            this.$emit('files-changed', this.getQueuedFiles());
        }
    }
};
</script>

<style lang="scss" scoped>
.attachment-subsection {
    :deep(.dropzone) {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        background: #ffffff;
        min-height: 120px;
        padding: 1.5rem;
        transition: all 0.3s;

        &:hover {
            border-color: #adb5bd;
            background: #fafbfc;
        }

        .dz-message {
            margin: 0.5rem 0;
        }
    }
}
</style>
