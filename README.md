# HejPanel
An informative web app for Gymnázium Olomouc-Hejčín

## Setting up the development environment
1. Clone this repo
2. Download and install [XAMPP](https://www.apachefriends.org/download.html)
3. Open the XAMPP control panel, start Apache and MySQL, then click Shell
4. Log in to MariaDB as root, the default password is empty, so just hit enter for the password prompt
    ```sh
    mysql -u root -p
    ```
5. Run the `setup.sql` file from this repo
    ```sh
    source C:/path/to/HejPanel/setup.sql
    ```
6. In the `php` directory of this repo, rename `EXAMPLE.env.php` to `.env.php`
    - The `setup.sql` file you ran in the previous step created a MySQL user `hejpanel` with the password `hejpanel` and gave them the permissions to the database `hejpanel` - these are the default values configured in `EXAMPLE.env.php`
7. Create a symlink from the location where you cloned this repo to XAMPP's htdocs/HejPanel directory
    ```cmd
    mklink /D C:/path/to/xampp/htdocs/HejPanel C:/path/to/HejPanel
    ```
8. You should now be able to access HejPanel at [localhost/HejPanel](http://localhost/HejPanel)

> [!IMPORTANT]  
> The `setup.sql` file is currently incomplete. Right now, it's just enough to get to the main page. Any help to complete this file will be appreciated.
