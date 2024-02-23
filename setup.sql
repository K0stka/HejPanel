DROP DATABASE hejpanel;
CREATE DATABASE hejpanel;

DELETE USER 'hejpanel'@'localhost';
FLUSH PRIVILEGES;
CREATE USER 'hejpanel'@'localhost' IDENTIFIED BY 'hejpanel';
GRANT ALL PRIVILEGES ON hejpanel.* TO 'hejpanel'@'localhost';

USE hejpanel;

CREATE TABLE sessions (
    session_id VARCHAR(32),
    data TEXT,
    user TEXT,
    auth TEXT,
    subscription TEXT,
    fingerprint TEXT,
    expires INT
);

CREATE TABLE panels (
    show_override TEXT,
    approved BOOLEAN,
    show_from INT,
    show_till INT
);

CREATE TABLE jidelna_cache (
    date INT,
    data TEXT
);
