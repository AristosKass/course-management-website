# Verification

The package was checked locally with PHP 8.3.6 and MariaDB 10.11.14.

| Check | Coverage |
| --- | --- |
| PHP syntax | All 24 PHP source files |
| Static website | 74 checks across five pages: titles, headings, navigation, local links, and image alternative text |
| Dynamic HTTP tests | 124 checks: authentication, both student accounts, Tutor permissions, CRUD, uploads, downloads, escaping, CSRF, automatic announcements, and logout |
| Setup | Existing database tables are preserved and reinitialization is refused |
| Additional file checks | A database record pointing to a file outside the course-storage directories cannot download that file |
| Additional contact checks | CR/LF injection in the email subject is rejected |

## Repeat the checks

Run `python3 tests/check_static.py` from the repository root. To run the HTTP checks, initialize a disposable demo database, start the PHP server using the documented router command, leave `MAIL_ENABLED` disabled, and run:

```sh
python3 tests/smoke.py http://127.0.0.1:8000
```

The HTTP checks create temporary users, announcements, documents, and homework records, then remove them on success. They store one contact message and can leave uploaded test files on disk. If a check fails midway, temporary records may remain; a fresh demo database provides a clean rerun.

## Limits

Actual email delivery was not tested; no email was sent. Apache access rules are included but were not exercised against an Apache server. The HTTP tests used the local PHP router. Public server configuration must enforce the same file-access restrictions.

A visual browser review could not be completed because the local browser executable would not start in the verification environment. The existing responsive CSS is retained, but desktop and mobile layout should be checked in a browser on the user's computer.
