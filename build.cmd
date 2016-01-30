@ECHO OFF
md gnetwork
cd gnetwork
ECHO GIT -----------------------------------------------------------------------
git clone https://github.com/geminorum/gnetwork .
PAUSE
ECHO COMPOSER ------------------------------------------------------------------
composer install --no-dev --optimize-autoloader --prefer-dist
ECHO FINISHED ------------------------------------------------------------------
