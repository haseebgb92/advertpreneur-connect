#!/usr/bin/env python3
"""Reject stale marketplace/plugin ZIP archives and unexpected ZIP entries."""
from pathlib import Path, PurePosixPath
from zipfile import ZipFile
import hashlib

ROOT = Path(__file__).resolve().parents[1]
RELEASES = [
    ('releases/advertpreneur-connect-wordpress-v0.7.1.zip', 'wordpress'),
    ('releases/advertpreneur-connect-agensi-skill-v0.2.1.zip', 'skills'),
]
for package, source_dir in RELEASES:
    zpath = ROOT / package
    assert zpath.is_file(), f'Missing {zpath}'
    with ZipFile(zpath) as archive:
        assert archive.testzip() is None, package
        archived = set()
        for info in archive.infolist():
            name = info.filename
            path = PurePosixPath(name)
            assert not name.startswith('/') and '..' not in path.parts, f'Unsafe archive entry: {name}'
            if info.is_dir():
                continue
            original = ROOT / source_dir / name
            assert original.is_file(), f'ZIP file not in source tree: {name}'
            assert hashlib.sha256(archive.read(info)).digest() == hashlib.sha256(original.read_bytes()).digest(), f'Stale ZIP file: {name}'
            archived.add(name)
        source = {str(p.relative_to(ROOT / source_dir)).replace('\\', '/') for p in (ROOT / source_dir / 'advertpreneur-connect').rglob('*') if p.is_file()}
        assert archived == source, f'Source/archive file mismatch: {package}: missing={source-archived}, unexpected={archived-source}'
    print('Verified archive exactly matches source:', package, len(archived), 'files')
