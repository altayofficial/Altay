#!/usr/bin/env bash

echo "--- Installing BedrockProtocol from local repository."
echo "--- This allows you to perform integration tests using Altay, without immediately publishing new versions of the library."

cp composer.json composer-local-protocol.json
cp composer.lock composer-local-protocol.lock

export COMPOSER=composer-local-protocol.json
./bin/php7/bin/php /usr/bin/composer config repositories.bedrock-protocol path ../Protocol # kinda personal, isn't it

./bin/php7/bin/php /usr/bin/composer require altayofficial/bedrock-protocol:*@dev

./bin/php7/bin/php /usr/bin/composer install

echo "--- Local dependencies have been successfully installed."
echo "--- This script does not modify composer.json. To go back to the original dependency versions, simply run 'composer install'."

