#!/usr/bin/env bash
CWD_BASENAME=${PWD##*/}

# Cleanup before scoping
rm -rf pre-scoper
rm -rf vendor
rm -rf build

# Composer install and scoping
composer install --no-dev --prefer-dist
mv vendor/ pre-scoper/
php ./php-scoper.phar add-prefix -p ThirtyBeesStripe -n

# Scoping cleanup
mv build/pre-scoper vendor
rm -rf pre-scoper
rm -rf build

# Dump new autoloader
composer -o dump-autoload

FILES+=("logo.gif")
FILES+=("logo.png")
FILES+=("CONTRIBUTORS.md")
FILES+=("index.php")
FILES+=("${CWD_BASENAME}.php")
FILES+=("README.md")
FILES+=("classes/**")
FILES+=("controllers/**")
FILES+=("translations/**")
FILES+=("upgrade/**")
FILES+=("views/**")
FILES+=("vendor/**")

MODULE_VERSION="$(sed -ne "s/\\\$this->version *= *['\"]\([^'\"]*\)['\"] *;.*/\1/p" ${CWD_BASENAME}.php)"
MODULE_VERSION=${MODULE_VERSION//[[:space:]]}
ZIP_FILE="${CWD_BASENAME}/${CWD_BASENAME}-v${MODULE_VERSION}.zip"

echo "Going to zip ${CWD_BASENAME} version ${MODULE_VERSION}"

cd ..
for E in "${FILES[@]}"; do
  find ${CWD_BASENAME}/${E}  -type f -exec zip -9 ${ZIP_FILE} {} \;
done
