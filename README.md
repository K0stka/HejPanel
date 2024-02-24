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
6. Optionally review the default configuration specified in `.env.php`
7. Create a symlink from the location where you cloned this repo to XAMPP's htdocs/HejPanel directory
    ```cmd
    mklink /D C:/path/to/xampp/htdocs/HejPanel C:/path/to/HejPanel
    ```
8. You should now be able to access HejPanel at [localhost/HejPanel](http://localhost/HejPanel)
