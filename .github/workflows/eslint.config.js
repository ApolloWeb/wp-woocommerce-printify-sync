module.exports = {
    root: true,
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
    ignorePatterns: ['node_modules/', 'vendor/', '.github/'],
};
