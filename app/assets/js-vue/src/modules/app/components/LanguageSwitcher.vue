<template>
    <div class="dropdown language-switcher">
        <button class="btn btn-sm dropdown-toggle" type="button" data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
            <img :src="current.flag"> <span class="sidebar__label">{{ current.name }}</span>
        </button>
        <div class="dropdown-menu">
            <!-- A full reload on purpose: LocaleSubscriber stores the choice in a cookie and /me reports it back. -->
            <a v-for="lang in languages" :key="lang.code" class="dropdown-item" :href="`?locale=${lang.code}`">
                <img :src="lang.flag"> {{ lang.name }}
            </a>
        </div>
    </div>
</template>

<script>
const languages = [
    { code: 'pl', name: 'Polski', flag: '/imgs/pl.png' },
    { code: 'en', name: 'English', flag: '/imgs/en.png' },
]

export default {
    name: 'LanguageSwitcher',
    data: () => ({ languages }),
    computed: {
        current() {
            return languages.find(l => l.code === this.$i18n.locale) || languages[0]
        },
    },
}
</script>
