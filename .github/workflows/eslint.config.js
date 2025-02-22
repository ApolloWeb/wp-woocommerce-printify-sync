module.exports = {
    env: {
        node: true,
        browser: true,
        es2021: true,
    },
    extends: [
        'eslint:recommended'
    ],
    rules: {
        'no-unused-vars': 'error',
        'eqeqeq': 'error'
    },
    ignorePatterns: ["wp/", "vendor/", "node_modules/", ".github/"],
};
