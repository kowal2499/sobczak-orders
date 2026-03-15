<script>
import SidebarLayout from "@/components/layout/SidebarLayout.vue";
import AgreementLineRmShowcaseItem from "@/components/base/Showcase/AgreementLineRmShowcaseItem.vue";
import SidebarNavbar from "@/components/layout/SidebarNavbar.vue";
import ShowcaseBadge from '@/components/base/Showcase/ShowcaseBadge.vue'

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
        ShowcaseBadge,
    },

    mounted() {
        if (this.data?.arg?.date) {
            this.$emit('set-title', `${this.$t('schedule.weeklyOrdersInCapacity')} - ${getLocalDate(this.data?.arg?.date)}`)
        }
    },

    computed: {
        capacityData() {
            return (this.data?.events?.capacity || [])[0]
        },

        filteredSelectedData() {
            // this.capacityData
            let data = this.capacityData?.agreementLines || []
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
    data: () => ({ q: '' })
}
</script>

<template>
    <SidebarLayout>
          <template #header>
              <div class="d-flex flex-row justify-content-between mx-2 gap-2">
                  <ShowcaseBadge
                    :label="$t('schedule.weekCapacity')"
                    :value="String(capacityData.capacity)"
                    icon="cogs"
                  />
                  <ShowcaseBadge
                      :label="$t('schedule.capacityBurned')"
                      icon="cogs"
                  >
                      <template #value>
                          {{ capacityData.capacityBurned }} ({{ Math.min(Math.round((capacityData.capacityBurned / capacityData.capacity) * 100), 100) }}%)
                      </template>
                  </ShowcaseBadge>
                  <SidebarNavbar @search="q = $event" :show-excel-export-btn="false" />
              </div>
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