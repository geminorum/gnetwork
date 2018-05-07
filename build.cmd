@ECHO OFF
Title gNetwork Package Builder
md .build
cd .build
ECHO GIT -----------------------------------------------------------------------
CALL git clone https://github.com/geminorum/gnetwork .
ECHO COMPOSER ------------------------------------------------------------------
CALL composer install --no-dev --optimize-autoloader --prefer-dist -v
ECHO Yarn ----------------------------------------------------------------------
CALL yarn install
ECHO BUILD ---------------------------------------------------------------------
CALL yarn run build
ECHO FINISHED ------------------------------------------------------------------
cd ..
