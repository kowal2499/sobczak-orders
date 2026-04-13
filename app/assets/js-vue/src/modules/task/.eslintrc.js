module.exports = {
    rules: {
        // Podwójne cudzysłowy dla wszystkich stringów
        quotes: ["error", "double", { avoidEscape: true, allowTemplateLiterals: false }],
        // Średnik na końcu każdego polecenia
        semi: ["error", "always"],
        // Spacje wewnątrz klamer: { foo } zamiast {foo}
        "object-curly-spacing": ["error", "always"],
    },
};