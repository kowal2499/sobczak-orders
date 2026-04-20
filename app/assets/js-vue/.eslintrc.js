module.exports = {
    root: true,
    env: {
        browser: true,
        es2021: true,
    },
    parser: "vue-eslint-parser",
    parserOptions: {
        ecmaVersion: 2021,
        sourceType: "module",
    },
    plugins: ["vue"],
    extends: ["plugin:vue/essential"],
    rules: {},
};
