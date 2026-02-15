module.exports = {
    testEnvironment: 'node',
    roots: ['<rootDir>/js-vue'],
    testMatch: ['<rootDir>/js-vue/__tests__/**/*.test.js'],
    transform: {
        '^.+\\.js$': 'babel-jest'
    },
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/js-vue/src/$1'
    }
}
