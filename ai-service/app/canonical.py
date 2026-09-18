from __future__ import annotations

from datetime import date, datetime

from .schemas import CandidateDocument


def _plain_number(value: float) -> str:
    return format(value, ".10g")


def _date_value(value: date | datetime) -> str:
    if isinstance(value, datetime):
        return value.date().isoformat()

    return value.isoformat()


def build_canonical_metadata(candidate: CandidateDocument) -> str:
    """Turn structured metadata into stable text for passage embedding."""
    fields: list[tuple[str, str | None]] = [
        ("Jenis Dokumen", candidate.scan_type),
        ("Judul", candidate.title),
        ("Nama File", candidate.file_name),
        ("Kode Dokumen", candidate.document_code),
        ("Part Number", candidate.part_number),
        ("Kata Kunci", candidate.keywords),
        ("Departemen", candidate.department),
        ("Kategori", candidate.category),
        ("Line", candidate.line),
        ("Shift", str(candidate.shift) if candidate.shift is not None else None),
        ("Dari Type", candidate.from_type),
        ("Ke Type", candidate.to_type),
        (
            "Cycle Time Dari",
            _plain_number(candidate.from_cycle_time)
            if candidate.from_cycle_time is not None
            else None,
        ),
        (
            "Cycle Time Ke",
            _plain_number(candidate.to_cycle_time)
            if candidate.to_cycle_time is not None
            else None,
        ),
        ("Type Axle", candidate.axle_type),
        ("Posisi", candidate.position),
        ("Part Number Brake", candidate.brake_part_number),
        (
            "Tanggal Dokumen",
            _date_value(candidate.document_date)
            if candidate.document_date is not None
            else None,
        ),
        (
            "Tanggal Dibuat",
            _date_value(candidate.created_at)
            if candidate.created_at is not None
            else None,
        ),
        (
            "Tanggal Diperbarui",
            _date_value(candidate.updated_at)
            if candidate.updated_at is not None
            else None,
        ),
    ]

    return ". ".join(f"{label}: {value}" for label, value in fields if value) + "."
