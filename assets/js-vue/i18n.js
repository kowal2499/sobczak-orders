import VueI18n from "vue-i18n";
import Vue from "vue";
import translationsDashboard from "./translations/dashboard";
import translationsGeneral from "./translations/general";
import translationsOrders from "./translations/orders";
import translationsProduction from "./translations/production";
import translationsTags from "./translations/tags";
import translationsAuth from "./translations/authorization";
import translationsUser from "./translations/user";
import {merge} from 'lodash';

Vue.use(VueI18n)

function loadDefaultMessages() {
    const locales = require.context('./src/locale', true, /\w{2}\.js$/i);
    let messages = {}

    locales.keys().forEach(key => {
        const lang = key.match(/([A-Za-z0-9-_]+)\..{2,3}$/i);
        if (lang[1]) {
            messages = merge(messages, {[lang[1]]: locales(key).default})
        }
    })
    return messages
}

function loadMessages() {
    const sources = [
        translationsDashboard,
        translationsGeneral,
        translationsOrders,
        translationsProduction,
        translationsTags,
        translationsAuth,
		translationsUser,
    ]
    let messages = {};
    for (let locale of sources) {
        for (let lang of Object.keys(locale)) {
            messages = merge(messages, {[lang]: locale[lang]})
        }
    }
    return messages
}

function loadModuleMessages() {
    const locales = require.context('./src/modules', true, /\.*locale\/\w{2}\.js$/i);
    let messages = {}

    locales.keys().forEach(key => {
        const matched = key.match(/\/([A-Za-z0-9-_]+)\/locale\/([A-Za-z0-9-_]+)\..{2,3}$/i);

        if (matched && matched.length) {

            // Nazwa modułu jest generowana na podstawie nazwy folderu nadrzędnego dla folderu 'locale'
            // CamelCase w nazwie jest konwertowany na małe znaki i następnie rozdzielany '_'
            const module = matched[1]
                .replace(/(^[A-Z])/, ([first]) => first.toLowerCase())
                .replace(/([A-Z])/g, ([letter]) => `_${letter.toLowerCase()}`);

            const locale = matched[2];

            messages = merge(messages, {
                [locale]: {
                    [module]: locales(key).default || {}
                }
            })
        }
    })
    return messages
}

export default new VueI18n({
    locale: 'pl',
    fallbackLocale: 'pl',
    messages: merge(loadDefaultMessages(), loadMessages(), loadModuleMessages())
});
