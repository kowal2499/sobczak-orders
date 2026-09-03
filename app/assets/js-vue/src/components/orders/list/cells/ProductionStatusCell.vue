<script>
import cellMixin from "@/components/base/BaseListing/components/cells/cellMixin"

export default {
    name: "ProductionStatusCell",

    mixins: [cellMixin],

    computed: {
        realProductions() {
            return Array.isArray(this.record.productions)
                ? this.record.productions.filter(production => !production.isGhost)
                : [];
        },

        // Zachowane zachowanie listy zamówień: o zakończeniu decyduje piąty element
        // tablicy (pakowanie), a nie wyszukanie działu po slugu.
        statusData() {
            if (this.realProductions.length === 0) {
                return { className: 'badge-danger', title: 'Nie zlecone' };
            }

            if (this.realProductions[4] && parseInt(this.realProductions[4].status) === 3) {
                return { className: 'badge-success', title: 'Zakończona' };
            }

            return { className: 'badge-primary', title: 'W trakcie' };
        }
    }
}
</script>

<template>
    <span class="badge badge-pill" :class="statusData.className">{{ $t(statusData.title) }}</span>
</template>
