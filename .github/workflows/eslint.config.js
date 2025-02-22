// eslint.config.js
export default [
    {
        files: ["**/*.js"],
        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",
            globals: {
                // If you need specific globals from `env`:
                window: "readonly",
                document: "readonly",
                navigator: "readonly",
                process: "readonly",
            },
        },
        rules: {
            "no-unused-vars": "error",
            "eqeqeq": "error",
        },
        ignores: ["wp/", "vendor/", "node_modules/", ".github/"],
    },
];
