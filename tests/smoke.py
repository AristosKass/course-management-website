#!/usr/bin/env python3
"""Exercise Part B on a LOCAL demo server; temporary records are removed on success.

Usage: python3 tests/smoke.py http://127.0.0.1:8000
Use a disposable demo database, with MAIL_ENABLED disabled.
"""
import html
import http.cookiejar
import re
import sys
import uuid
from urllib import error, parse, request


class NoRedirect(request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Client:
    def __init__(self, base):
        self.base = base.rstrip("/") + "/"
        self.opener = request.build_opener(
            NoRedirect(), request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.token = None

    def get(self, path):
        return self.send(path)

    def send(self, path, fields=None, upload=None, token=True):
        headers = {}
        body = None
        if fields is not None:
            fields = dict(fields)
            if token and self.token:
                fields["csrf_token"] = self.token
            if upload:
                boundary = "demo-" + uuid.uuid4().hex
                body = b""
                for key, value in fields.items():
                    body += (f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"'
                             f'\r\n\r\n{value}\r\n').encode()
                name, filename, data = upload
                body += (f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; '
                         f'filename="{filename}"\r\nContent-Type: application/octet-stream'
                         f'\r\n\r\n').encode() + data + f'\r\n--{boundary}--\r\n'.encode()
                headers["Content-Type"] = "multipart/form-data; boundary=" + boundary
            else:
                body = parse.urlencode(fields).encode()
        req = request.Request(parse.urljoin(self.base, path), data=body, headers=headers)
        try:
            response = self.opener.open(req, timeout=10)
        except error.HTTPError as response_error:
            response = response_error
        content = response.read()
        found = re.search(rb'name="csrf_token" value="([^"]+)"', content)
        if found:
            self.token = found.group(1).decode()
        return response.code, content, response.headers

    def login(self, email, password):
        check(self.get("login.php")[0] == 200, "Login form")
        check(self.send("login.php", {"email": email, "password": password})[0] == 302,
              "Valid login: " + email)


checks = 0


def check(condition, description):
    global checks
    if not condition:
        raise AssertionError(description)
    checks += 1


def item_ids(client, page, form):
    status, content, _ = client.get(page)
    check(status == 200, "List: " + page)
    return {int(value) for value in re.findall(
        re.escape(form).encode() + rb'\?id=(\d+)', content)}


def main():
    base = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000"
    parsed = parse.urlsplit(base)
    if parsed.scheme != "http" or parsed.hostname not in ("127.0.0.1", "localhost", "::1"):
        raise SystemExit("Tests are restricted to a local HTTP demo server.")
    marker = "Smoke " + uuid.uuid4().hex[:10]
    anonymous = Client(base)
    for page in ("index.php", "announcements.php", "documents.php", "homework.php",
                 "communication.php", "users.php", "download.php?type=document&id=1"):
        status, _, headers = anonymous.get(page)
        check(status == 302 and headers.get("Location") == "login.php", "Authentication: " + page)
    for private in ("downloads/file1.doc", "uploads/", "config.php", "config.example.php",
                    "includes/bootstrap.php", "setup.php", "router.php"):
        check(anonymous.get(private)[0] in (403, 404), "Private path: " + private)
    check(anonymous.get("style.css")[0] == 200, "Public stylesheet")
    check(anonymous.get("assets/img/banner.png")[0] == 200, "Public image")
    anonymous.get("login.php")
    check(anonymous.send("login.php", {"email": "tutor@example.test", "password": "wrong"})[0] == 200,
          "Wrong password")
    check(anonymous.send("login.php", {"email": "' OR 1=1 --", "password": "wrong"})[0] == 200,
          "SQL injection does not authenticate")
    check(anonymous.send("login.php", {"email": "tutor@example.test", "password": "TutorDemo123!"},
                         token=False)[0] == 403, "Login rejects missing CSRF token")

    tutor = Client(base)
    tutor.login("tutor@example.test", "TutorDemo123!")
    for page in ("index.php", "announcements.php", "documents.php", "homework.php",
                 "communication.php", "users.php"):
        status, body, _ = tutor.get(page)
        check(status == 200 and b"Fatal error" not in body, "Tutor page: " + page)
    students = []
    for email, password in (("student.a@example.test", "StudentADemo123!"),
                            ("student.b@example.test", "StudentBDemo123!")):
        student = Client(base)
        student.login(email, password)
        students.append(student)
        for page in ("index.php", "announcements.php", "documents.php", "homework.php", "communication.php"):
            check(student.get(page)[0] == 200, "Student page: " + page)
        for page in ("users.php", "user_form.php", "announcement_form.php", "document_form.php",
                     "homework_form.php", "user_delete.php?id=1", "announcement_delete.php?id=1"):
            check(student.get(page)[0] == 403, "Student denied tutor operation: " + page)
        status, body, _ = student.get("documents.php")
        check(b"document_delete.php" not in body, "Student has no management controls")
        status, body, headers = student.get("download.php?type=document&id=1")
        check(status == 200 and body.startswith(b"{\\rtf"), "Authenticated document download")
        check(headers.get("Cache-Control") == "private, no-store", "Private download cache")
    check(students[0].get("download.php?type=document&id=999999")[0] == 404, "Missing file record")
    check(students[0].get("download.php?type=other&id=1")[0] == 400, "Invalid download type")

    # Announcement CRUD, escaping, and protection against mutation by GET/CSRF.
    before = item_ids(tutor, "announcements.php", "announcement_form.php")
    tutor.get("announcement_form.php")
    fields = {"ann_date": "2026-01-01", "subject": marker, "body": "<script>alert(1)</script>"}
    check(tutor.send("announcement_form.php", fields)[0] == 302, "Create announcement")
    added = item_ids(tutor, "announcements.php", "announcement_form.php") - before
    check(len(added) == 1, "One new announcement")
    announcement = added.pop()
    body = tutor.get("announcements.php")[1]
    check(b"&lt;script&gt;alert(1)&lt;/script&gt;" in body and b"<script>alert(1)</script>" not in body,
          "Stored content is escaped")
    check(tutor.get(f"announcement_delete.php?id={announcement}")[0] == 405, "GET cannot delete")
    check(tutor.send("announcement_delete.php", {"id": announcement}, token=False)[0] == 403,
          "Deletion needs CSRF token")
    check(tutor.send("announcement_delete.php", {"id": announcement, "csrf_token": "wrong"},
                     token=False)[0] == 403, "Deletion rejects wrong CSRF token")
    fields["subject"] = marker + " edited"
    check(tutor.send(f"announcement_form.php?id={announcement}", fields)[0] == 302, "Edit announcement")
    check(fields["subject"].encode() in tutor.get("announcements.php")[1], "Edited announcement is visible")
    check(tutor.send("announcement_delete.php", {"id": announcement})[0] == 302, "Delete announcement")
    check(item_ids(tutor, "announcements.php", "announcement_form.php") == before, "Announcement cleanup")

    # Document upload, authenticated retrieval, and rejected executable extensions.
    before = item_ids(tutor, "documents.php", "document_form.php")
    tutor.get("document_form.php")
    data = (marker + " document\n").encode()
    check(tutor.send("document_form.php", {"title": marker, "description": "Demo document"},
                     ("file", "sample.txt", data))[0] == 302, "Upload document")
    added = item_ids(tutor, "documents.php", "document_form.php") - before
    check(len(added) == 1, "One new document")
    document = added.pop()
    check(students[0].get(f"download.php?type=document&id={document}")[1] == data, "Uploaded content matches")
    _, form, _ = tutor.get(f"document_form.php?id={document}")
    upload_path = re.search(rb'uploads/documents/[^<\s]+', form)
    check(upload_path is not None, "Upload path is recorded")
    check(students[0].get(upload_path.group(0).decode())[0] == 403, "Direct upload URL is protected")
    check(tutor.send(f"document_form.php?id={document}", {"title": marker + " edited", "description": "Updated"})[0] == 302,
          "Edit document without replacing file")
    tutor.get("document_form.php")
    status, _, _ = tutor.send("document_form.php", {"title": "Rejected", "description": "Executable"},
                              ("file", "payload.php", b"<?php echo 'unsafe';"))
    check(status == 200, "Executable upload is rejected in the form")
    check(item_ids(tutor, "documents.php", "document_form.php") == before | {document}, "Rejected upload creates no record")
    check(tutor.send("document_delete.php", {"id": document})[0] == 302, "Delete document")
    check(item_ids(tutor, "documents.php", "document_form.php") == before, "Document cleanup")

    # A new homework creates an announcement; deleting it removes the announcement.
    before_hw = item_ids(tutor, "homework.php", "homework_form.php")
    before_ann = item_ids(tutor, "announcements.php", "announcement_form.php")
    tutor.get("homework_form.php")
    fields = {"goals": marker + "\nSecond goal", "deliverables": "Source code\nReport", "due_date": "31/12/2026"}
    check(tutor.send("homework_form.php", fields, ("spec_file", "assignment.txt", data))[0] == 302, "Create homework")
    added = item_ids(tutor, "homework.php", "homework_form.php") - before_hw
    check(len(added) == 1, "One new homework")
    homework = added.pop()
    check(len(item_ids(tutor, "announcements.php", "announcement_form.php") - before_ann) == 1,
          "Automatic homework announcement")
    check(students[0].get(f"download.php?type=homework&id={homework}")[1] == data, "Homework download")
    fields["goals"] += "\nEdited goal"
    check(tutor.send(f"homework_form.php?id={homework}", fields)[0] == 302, "Edit homework")
    check(tutor.send("homework_delete.php", {"id": homework})[0] == 302, "Delete homework")
    check(item_ids(tutor, "homework.php", "homework_form.php") == before_hw, "Homework cleanup")
    check(item_ids(tutor, "announcements.php", "announcement_form.php") == before_ann, "Related announcement cleanup")

    # User CRUD and live session authorization when a role changes or an account is deleted.
    before = item_ids(tutor, "users.php", "user_form.php")
    email = "smoke-" + uuid.uuid4().hex[:10] + "@example.test"
    tutor.get("user_form.php")
    fields = {"first_name": "Smoke", "last_name": "User", "email": email,
              "role": "student", "password": "SmokeDemo123!"}
    check(tutor.send("user_form.php", fields)[0] == 302, "Create user")
    added = item_ids(tutor, "users.php", "user_form.php") - before
    check(len(added) == 1, "One new user")
    user = added.pop()
    temporary = Client(base)
    temporary.login(email, "SmokeDemo123!")
    check(temporary.get("users.php")[0] == 403, "New student has no management access")
    fields.update({"role": "tutor", "password": ""})
    check(tutor.send(f"user_form.php?id={user}", fields)[0] == 302, "Edit user role")
    check(temporary.get("users.php")[0] == 200, "Role change updates existing session")
    fields["email"] = "tutor@example.test"
    check(tutor.send("user_form.php", fields)[0] == 200, "Duplicate email is rejected")
    check(item_ids(tutor, "users.php", "user_form.php") == before | {user}, "No duplicate user")
    check(tutor.send("user_delete.php", {"id": user})[0] == 302, "Delete user")
    check(temporary.get("index.php")[0] == 302, "Deleted account loses existing session")
    check(item_ids(tutor, "users.php", "user_form.php") == before, "User cleanup")

    tutor.get("communication.php")
    check(tutor.send("communication.php", {"sender": "sender@example.test", "subject": marker,
                                           "message": "Local demo message"})[0] == 302, "Contact form stores message")
    check(tutor.get("logout.php")[0] == 405, "GET does not log out")
    check(tutor.send("logout.php", {})[0] == 302, "POST logout")
    check(tutor.get("index.php")[0] == 302, "Protected pages require login after logout")
    print(f"Part B: {checks} HTTP checks passed. Temporary CRUD records were removed.")


if __name__ == "__main__":
    main()
