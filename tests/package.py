"""Check release contents and non-destructive upgrade instructions."""
from pathlib import Path
import io
import re
import tarfile
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
ns = {'w': 'http://www.woltlab.com'}
manifest = ET.parse(root / 'woltlab/package.xml')
version = manifest.findtext('w:packageinformation/w:version', namespaces=ns)
with tarfile.open(root / f'dist/de.eulenthal.wcf.lawLibrary_{version}.tar') as package:
    for upgrade in manifest.findall('w:instructions[@type="update"]', ns):
        steps = {step.attrib['type']: step.text for step in upgrade}
        assert {'file', 'template', 'acpTemplate', 'language', 'option', 'eventListener'} <= steps.keys()
        if 'sql' in steps:
            assert upgrade.attrib['fromversion'] == '1.0.0'
            sql = package.extractfile(steps['sql']).read().decode()
            assert 'CREATE TABLE wcf1_law_library_bookmark' in sql
            assert not re.search(r'^\s*(INSERT|UPDATE|DELETE|DROP)\b', sql, re.M)
        assert 'script' not in steps and 'database' not in steps
    assert {i.attrib['fromversion'] for i in manifest.findall('w:instructions[@type="update"]', ns)} == {'1.0.0', '1.1.0', '1.2.0', '1.2.1', '1.3.0'}
    for archive in ['files.tar', 'templates.tar', 'acptemplates.tar']:
        with tarfile.open(fileobj=io.BytesIO(package.extractfile(archive).read())) as nested:
            names = nested.getnames()
            assert not any('.local' in name or 'node_modules' in name for name in names)
    assert 'install.sql' not in [i.text for u in manifest.findall('w:instructions[@type="update"]', ns) for i in u]
print('PASS: all supported upgrade paths preserve stored collection and ship complete release code')
