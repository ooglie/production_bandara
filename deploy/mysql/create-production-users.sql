-- Bandara Frozen MySQL 8.x least-privilege account template.
-- Replace the database name, host, usernames, and every CHANGE_ME password.
-- Run as a database administrator. Do not grant FILE, PROCESS, SUPER,
-- CREATE USER, or GRANT OPTION to these accounts.

CREATE USER IF NOT EXISTS 'bandara_app'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_ME_LONG_RANDOM_RUNTIME_PASSWORD';
ALTER USER 'bandara_app'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_ME_LONG_RANDOM_RUNTIME_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON `bandarafrozen`.*
    TO 'bandara_app'@'127.0.0.1';

CREATE USER IF NOT EXISTS 'bandara_deploy'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_ME_LONG_RANDOM_DEPLOY_PASSWORD';
ALTER USER 'bandara_deploy'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_ME_LONG_RANDOM_DEPLOY_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE,
      CREATE, ALTER, INDEX, DROP, REFERENCES,
      CREATE TEMPORARY TABLES, LOCK TABLES
    ON `bandarafrozen`.*
    TO 'bandara_deploy'@'127.0.0.1';

CREATE USER IF NOT EXISTS 'bandara_backup'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_ME_LONG_RANDOM_BACKUP_PASSWORD';
ALTER USER 'bandara_backup'@'127.0.0.1'
    IDENTIFIED BY 'CHANGE_ME_LONG_RANDOM_BACKUP_PASSWORD';
GRANT SELECT, SHOW VIEW, TRIGGER
    ON `bandarafrozen`.*
    TO 'bandara_backup'@'127.0.0.1';

SHOW GRANTS FOR 'bandara_app'@'127.0.0.1';
SHOW GRANTS FOR 'bandara_deploy'@'127.0.0.1';
SHOW GRANTS FOR 'bandara_backup'@'127.0.0.1';
