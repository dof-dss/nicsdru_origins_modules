# Mediahasher

This program is to be used in conjunction with the  [Media Duplciates](https://www.drupal.org/project/media_duplicates) module.

The binary (dof-dss-mediahasher) is compiled for Linux environments and is executable on upsun or within the ddev container.

It takes the following arguments:

#### Required
 | Argument   | Purpose         | Example                    |
 |:-----------|:----------------|:---------------------------|
 | --dsn      | Database DSN    | e.g. db:db@tcp(db:3306)/db |
 | --web-root | Drupal web root | e.g. /var/www/html/web     |

#### Optional

| Argument      | Purpose                                                | Example                                    |
 |:--------------|:-------------------------------------------------------|:-------------------------------------------|
| --public-dir  | Drupal public:// uri path                              | e.g. /var/www/html/web/sites/default/files |
| --private-dir | Drupal private:// uri path                             | e.g. /var/www/html/private                 |
| --batch       | Number of rows to process                              |                                            |
| --dry-run     | Process rows without writing updates to the database   |                                            |
| --force       | Process all rows including those with a checksum value |                                            |
| --verbose     | Display information about each media file              |                                            |
