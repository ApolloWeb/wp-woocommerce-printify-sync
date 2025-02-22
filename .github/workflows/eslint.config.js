import js from "@eslint/js";
import recommended from "eslint-config-recommended";

export default [
  js.configs.recommended,
  recommended,
  {
    rules: {
      "no-unused-vars": "warn",
      "no-console": "off",
      "indent": ["error", 2],
      "quotes": ["error", "double"],
    },
  },
];
