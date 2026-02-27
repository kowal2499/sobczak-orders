<script>
import SidebarLayout from "@/components/layout/SidebarLayout.vue";
import AgreementLineRmShowcaseItem from "@/components/base/Showcase/AgreementLineRmShowcaseItem.vue";
import SidebarNavbar from "@/components/layout/SidebarNavbar.vue";
import {getLocalDate} from '@/helpers'
import {deburr} from "lodash";
export default {
    name: "CapacitySidebar",

    props: {
        data: {
            type: Object,
            default: () => ({}),
        }
    },

    components: {
        AgreementLineRmShowcaseItem,
        SidebarLayout,
        SidebarNavbar,
    },

    mounted() {
        if (this.data?.arg?.date) {
            this.$emit('set-title', `Zamówienia wpływające na obłożenie tygodniowe - ${getLocalDate(this.data?.arg?.date)}`)
        }
    },

    computed: {
        filteredSelectedData() {
            const events = this.data?.events?.capacity || []
            let data = events[0]?.agreementLines || []
            const lines = Object.values(data)

            if (!this.q) {
                return lines
            }

            const searchTerm = deburr(this.q).toLowerCase()

            return lines.filter(item =>
                deburr(item.q || '').toLowerCase().includes(searchTerm)
            )
        },
    },
    data: () => ({
        q: '',
    })
}
</script>

<template>
    <SidebarLayout>
          <template #header>
              <SidebarNavbar @search="q = $event" :show-excel-export-btn="false"/>
          </template>

          <template #content>
              <AgreementLineRmShowcaseItem
                  v-for="line in filteredSelectedData"
                  :key="line.id"
                  :data="line"
              />
          </template>
    </SidebarLayout>
</template>

<style scoped lang="scss">

</style>