#!/usr/bin/env bash
CWD_BASENAME=${PWD##*/}

# Cleanup before scoping
rm -rf vendor

# Composer install and scoping
composer install --no-dev --prefer-dist


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
