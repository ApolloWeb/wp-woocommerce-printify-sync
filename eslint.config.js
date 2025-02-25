module.exports = {
  env: {
    browser: true,
    es6: true,
    jquery: true,
    node: true
  },
  extends: [
    "eslint:recommended",
    "plugin:react/recommended",
    "plugin:@wordpress/eslint-plugin/recommended"
  ],
  globals: {
    wp: "readonly"
  },
  parserOptions: {
    ecmaFeatures: {
      jsx: true
    },
    ecmaVersion: 2020,
    sourceType: "module"
  },
  plugins: [
    "react",
    "@wordpress"
  ],
  rules: {
    indent: ["error", 2],
    linebreak-style: ["error", "unix"],
    quotes: ["error", "single"],
    semi: ["error", "always"],
    "no-console": "warn",
    "no-unused-vars": "warn"
  }
};