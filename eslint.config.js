module.exports = [
    {
      ignores: ["node_modules", "dist", "build", "**/*.min.js"],
      languageOptions: {
        ecmaVersion: "latest",
        sourceType: "module"
      },
      rules: {
        "no-unused-vars": "error",
        "no-console": "warn",
        "eqeqeq": "error",
        "curly": "error",
        "semi": ["error", "always"],
        "quotes": ["error", "double"]
      }
    }
  ];
  