module.exports = {
    "*.php": ["phpcbf --standard=PSR12,WordPress-Extra"],
    "*.js": ["eslint --fix"],
    "*.css": ["stylelint --fix"],
    "*.scss": ["stylelint --fix"],
    "*.{php,js,css,scss}": ["prettier --write"]
};