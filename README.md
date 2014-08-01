cronox
======
Minimum Requirements:
	- PHP 5.2.x
	- PDO SQLite Driver installed
	- SQLite3 installed on system
		sudo apt-get update
		sudo apt-get install php5-cli php5-dev make sqlite3 libsqlite3-0 libsqlite3-dev php5-sqlite

Setting up project:
	- Turn Off Magic Quotes:
		(1a) If you have access to php.ini file, make sure the following option are all Off:
				magic_quotes_gpc = Off
				magic_quotes_sybase = Off
				magic_quotes_runtime = Off
		(1b) If you do not have access to php.ini file, add the following to your .htaccess file:
				php_flag magic_quotes_gpc Off
				php_flag magic_quotes_sybase Off
				php_flag magic_quotes_runtime Off
				
	- Setup database:
		(1) sqlite3 app/sqlite.db.cache
		(2) .exit
		(3) php app/console doctrine:schema:create
		(4) sudo chmod 664 app/sqlite.db.cache
		(5) sudo chmod 775 app

	- Setup cache:
		(1) sudo php app/console cache:clear --env=dev
		(2) sudo php app/console cache:warmup --env=dev

    - Setup capistrano deployment (optional; only if you want to use capistrano):
        (1) visudo
			# Allow capistrano deployments to restart services
			cronox ALL=NOPASSWD:/usr/sbin/service
			
	- Test it:
		(1a) http://yourEnvironment/backup-system/web/
		(or 1b) http://localhost:[PORT IF NEEDED]/backup-system/web/app_dev.php (ON LOCALHOST ONLY WITH PROFILER)

	- Errors you could meet:
		- Database not found / accessible:
			(1) Make sure it exists
			(2) Make sure the name is sqlite.db.cache
		- Dabatase cannot be written OR DB Error 14:
			(1) sudo chmod 664 app/sqlite.db.cache
			(2) sudo chmod 775 app

Other:
	- Reinitialise DB if necessary:
		(1) php app/console doctrine:schema:drop --force
		(2) php app/console doctrine:schema:create

	- Clear cache:
		(1a) sudo php app/console cache:clear --env=dev
		(or 1b) sudo rm -rf app/cache/*

