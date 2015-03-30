Simple CLI tool to manage Joomla installations.

### Archive

`jcli archive`

Zip all files in the project, excluding temporary directories (`logs`, `tmp`, `cache`, `administrator/cache`).   

`jcli archive --db`

Same as above, but will also include a dump of the database.
 
### Database

`jcli db --export <path>`

Will dump the database.

`jcli db --import <path>`

Will import the database. If database exists, you'll be given the choice to dump it first.
 
### Config

`jcli config`

Change local configuration variables (`host`, `db`, `user`, `password`, `log_path`, `tmp_path`);

