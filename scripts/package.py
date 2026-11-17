"""Build a self-contained WoltLab 6.2.7 package (no Node on the server)."""
from pathlib import Path
import io
import json
import tarfile
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
PACKAGE = ROOT / 'woltlab'
OUTPUT = ROOT / 'dist'
OUTPUT.mkdir(exist_ok=True)
categories = json.loads((PACKAGE / 'categories.json').read_text())
payload = json.dumps({'version': 1, 'categories': categories, 'laws': []}, ensure_ascii=False, separators=(',', ':'))
sql = '''CREATE TABLE wcf1_law_library (
    libraryID INT(10) NOT NULL,
    revision INT(10) NOT NULL DEFAULT 0,
    payload MEDIUMTEXT NOT NULL,
    updatedAt INT(10) NOT NULL DEFAULT 0,
    updatedBy INT(10) NOT NULL DEFAULT 0,
    PRIMARY KEY (libraryID)
);
INSERT INTO wcf1_law_library (libraryID, payload) VALUES (1, '%s');
''' % payload.replace('\\', '\\\\').replace("'", "''")

def archive(entries):
    stream = io.BytesIO()
    with tarfile.open(fileobj=stream, mode='w', format=tarfile.USTAR_FORMAT) as tar:
        for name, contents in sorted(entries.items()):
            info = tarfile.TarInfo(name)
            info.size = len(contents)
            info.mode = 0o644
            info.mtime = 0
            tar.addfile(info, io.BytesIO(contents))
    return stream.getvalue()

def files(directory):
    return {str(path.relative_to(directory)): path.read_bytes() for path in directory.rglob('*') if path.is_file()}

for path in PACKAGE.rglob('*.xml'):
    ET.parse(path)
assets = files(PACKAGE / 'files')
entries = {p.name: p.read_bytes() for p in PACKAGE.glob('*.xml')}
entries.update({f'language/{p.name}': p.read_bytes() for p in (PACKAGE/'language').glob('*.xml')})
entries['files.tar'] = archive(assets)
entries['templates.tar'] = archive(files(PACKAGE / 'templates'))
entries['acptemplates.tar'] = archive(files(PACKAGE / 'acptemplates'))
bookmarks = '''CREATE TABLE wcf1_law_library_bookmark (
    userID INT(10) NOT NULL,
    lawKey VARCHAR(80) NOT NULL,
    PRIMARY KEY (userID, lawKey),
    CONSTRAINT law_library_bookmark_user_fk FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE
);
'''
entries['install.sql'] = (sql + bookmarks).encode()
entries['update.sql'] = bookmarks.encode()
version = ET.parse(PACKAGE / 'package.xml').findtext('{http://www.woltlab.com}packageinformation/{http://www.woltlab.com}version')
output = OUTPUT / f'de.eulenthal.wcf.lawLibrary_{version}.tar'
output.write_bytes(archive(entries))
print(f'WoltLab-Paket: {output}')
