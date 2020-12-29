#!/bin/bash
/usr/local/bin/php -d'error_log=/dev/stderr' main.php postload 2012-3-28 2012-4-30 &&  /usr/local/bin/php -d'error_log=/dev/stderr' main.php 'paytrade' 2012-4-28 2012-4-30
/usr/local/bin/php -d'error_log=/dev/stderr' main.php postload 2012-4-1 2012-4-30 &&  /usr/local/bin/php -d'error_log=/dev/stderr' main.php 'cityday' 2012-4-1 2012-4-30
/usr/local/bin/php -d'error_log=/dev/stderr' main.php postload 2012-3-1 2012-3-31 &&  /usr/local/bin/php -d'error_log=/dev/stderr' main.php 'cityday' 2012-3-1 2012-3-31
/usr/local/bin/php -d'error_log=/dev/stderr' main.php postload 2012-1-1 2012-2-29 &&  /usr/local/bin/php -d'error_log=/dev/stderr' main.php 'stattrade,paytrade,cityday' 2012-2-1 2012-2-29
/usr/local/bin/php -d'error_log=/dev/stderr' main.php postload 2011-12-1 2012-1-31 &&  /usr/local/bin/php -d'error_log=/dev/stderr' main.php 'stattrade,paytrade,cityday' 2012-1-1 2012-1-31
