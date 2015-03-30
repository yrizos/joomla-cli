Simple CLI tool to manage Joomla installations.

Archive
===

`jcli archive`

Zip all files in the project, excluding temporary directories (`logs`, `tmp`, `cache`, `administrator/cache`).   

`jcli archive --db`

Same as above, but will also include a dump of the database (needs `mysqldump`).
 
Config
===

`jcli config`

Change local configuration variables (`host`, `db`, `user`, `password`, `log_path`, `tmp_path`);

