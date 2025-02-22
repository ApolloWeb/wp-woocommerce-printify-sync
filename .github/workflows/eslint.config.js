export default [
    {
        files: ["**/*.js"],
        languageOptions: {
            ecmaVersion: "latest",
            sourceType: "module",
            globals: {
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
        ignores: ["vendor/", "node_modules/", "wp/"],
    },
];
