<script>
import CollapsibleList from '../../../components/base/CollapsibleList'
import CoolLightBox from 'vue-cool-lightbox';
import 'vue-cool-lightbox/dist/vue-cool-lightbox.min.css'

export default {
    name: 'Attachments',

    props: {
        attachments: {
            type: Array,
            default: () => [],
        },
    },

    components: {
        CollapsibleList,
        CoolLightBox,
    },

    computed: {
        imageAttachments() {
            return this.attachments.filter(att => this.isImage(att));
        },

        lightboxItems() {
            return this.imageAttachments.map(att => ({
                src: att.viewPath || att.path,
                title: `${att.originalName}.${att.extension}`
            }));
        }
    },

    methods: {
        isImage(attachment) {
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            return attachment.extension && imageExtensions.includes(attachment.extension.toLowerCase());
        },
        openLightbox(attachmentIndex) {
            // Znajdź indeks tego załącznika w tablicy imageAttachments
            const attachment = this.attachments[attachmentIndex];
            const lightboxIndex = this.imageAttachments.findIndex(img => img.id === attachment.id);

            if (lightboxIndex !== -1) {
                this.lightboxIndex = lightboxIndex;
            }
        }
    },

    data: () => ({
        lightboxIndex: null
    }),
}
</script>

<template>
    <div>
        <CollapsibleList :items="attachments" :visible-rows="2" class="sb-attachments">
            <template #default="{ item, index }">
                <a
                    v-if="isImage(item)"
                    href="#"
                    @click.prevent="openLightbox(index)"
                    style="cursor: pointer"
                >
                    <div
                        class="d-flex flex-row align-items-center"
                        style="width: 100%"
                        :id="item.id"
                    >
                        <img
                            :src="item.thumbnail"
                            :alt="item.name"
                            v-if="item.thumbnail"
                        >
                        <span
                            class="ml-1 link-txt"
                            style="display: block; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                        >{{ ''.concat(item.originalName, '.', item.extension) }}</span>
                    </div>

                    <b-tooltip :target="String(item.id)" placement="right">
                        {{ ''.concat(item.originalName, '.', item.extension) }}
                    </b-tooltip>
                </a>

                <!-- Dla plików niebędących obrazami - normalny link do pobrania -->
                <a
                    v-else
                    :href="item.path"
                    target="_blank"
                >
                    <div
                        class="d-flex flex-row align-items-center"
                        style="width: 100%;"
                        :id="item.id"
                    >
                        <img
                            :src="item.thumbnail"
                            :alt="item.name"
                            v-if="item.thumbnail"
                        >
                        <span
                            class="ml-1 link-txt"
                            style="display: block; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                        >{{ ''.concat(item.originalName, '.', item.extension) }}</span>
                    </div>

                    <b-tooltip :target="String(item.id)" placement="right">
                        {{ ''.concat(item.originalName, '.', item.extension) }}
                    </b-tooltip>
                </a>
            </template>
        </CollapsibleList>

        <!-- Lightbox dla obrazów -->
        <CoolLightBox
            :items="lightboxItems"
            :index="lightboxIndex"
            @close="lightboxIndex = null"
            :slideshow="false"
        />
    </div>
</template>

<style scoped lang="scss">
.sb-attachments {
    width: 170px;

    img {
        width: 24px;
    }

    ul {
        list-style-type: none;
        margin: 0;
        padding: 0;

        li {
            margin-bottom: 20px;

            a:hover {
                text-decoration: none;

                .link-txt {
                    text-decoration: underline;
                }
            }
        }

    }
}

// Lightbox images powinny mieć pełny rozmiar
.cool-lightbox {
    img {
        width: auto !important;
        max-width: 100%;
        height: auto;
    }
}
</style>