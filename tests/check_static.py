#!/usr/bin/env python3
"""Check page titles, navigation, images, and local download links in Part A."""
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlsplit


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.resources = []
        self.ids = set()
        self.in_title = self.in_h1 = False
        self.title = self.heading = ""
        self.navigation = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if "id" in attrs:
            self.ids.add(attrs["id"])
        if tag == "title":
            self.in_title = True
        if tag == "h1":
            self.in_h1 = True
        for name in ("href", "src", "action"):
            if attrs.get(name):
                self.resources.append(attrs[name])
        if tag == "img":
            assert attrs.get("alt"), "Image is missing alternative text"
        if tag == "a" and attrs.get("href", "").endswith(".html"):
            self.navigation.append(attrs["href"])

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False
        if tag == "h1":
            self.in_h1 = False

    def handle_data(self, data):
        if self.in_title:
            self.title += data
        if self.in_h1:
            self.heading += data


def main():
    base = Path(__file__).resolve().parents[1] / "part-a"
    names = {"index.html", "announcement.html", "communication.html",
             "documents.html", "homework.html"}
    checks = 0
    for name in sorted(names):
        file = base / name
        page = Page()
        page.feed(file.read_text(encoding="utf-8"))
        assert page.title.strip() == page.heading.strip() and page.title.strip(), name
        checks += 1
        assert names.issubset(page.navigation), f"Incomplete navigation: {name}"
        checks += 1
        for resource in page.resources:
            url = urlsplit(resource)
            if url.scheme or url.netloc:
                continue
            if not url.path:
                assert not url.fragment or url.fragment in page.ids, (name, resource)
            else:
                assert (file.parent / unquote(url.path)).is_file(), (name, resource)
            checks += 1
    print(f"Part A: {checks} checks passed across five pages.")


if __name__ == "__main__":
    main()
