from __future__ import annotations

import calendar
import re
import unicodedata
from datetime import date, timedelta
from typing import Iterable

from .schemas import KnownValues, SearchEntities, VocabularyInput, VocabularyValue


MONTHS = {
    "januari": 1,
    "january": 1,
    "februari": 2,
    "february": 2,
    "maret": 3,
    "march": 3,
    "april": 4,
    "mei": 5,
    "may": 5,
    "juni": 6,
    "june": 6,
    "juli": 7,
    "july": 7,
    "agustus": 8,
    "august": 8,
    "september": 9,
    "oktober": 10,
    "october": 10,
    "november": 11,
    "desember": 12,
    "december": 12,
}

NUMBER_WORDS = {
    "satu": 1,
    "dua": 2,
    "tiga": 3,
    "one": 1,
    "two": 2,
    "three": 3,
}


def normalize_for_matching(value: str) -> str:
    normalized = unicodedata.normalize("NFKC", value).casefold()
    normalized = re.sub(r"[^a-z0-9]+", " ", normalized)
    return re.sub(r"\s+", " ", normalized).strip()


def _as_vocabulary_value(value: VocabularyInput) -> VocabularyValue:
    if isinstance(value, VocabularyValue):
        return value

    return VocabularyValue(value=value)


def _matches(query: str, value: str) -> bool:
    normalized_value = normalize_for_matching(value)

    if not normalized_value:
        return False

    return f" {normalized_value} " in f" {query} "


def _match_vocabulary(query: str, values: Iterable[VocabularyInput]) -> str | None:
    matches: list[tuple[int, str]] = []

    for raw_value in values:
        value = _as_vocabulary_value(raw_value)
        phrases = [value.value, *value.aliases]

        matched_phrases = [
            normalize_for_matching(phrase)
            for phrase in phrases
            if _matches(query, phrase)
        ]

        if matched_phrases:
            longest_phrase = max(matched_phrases, key=len)
            matches.append((len(longest_phrase), value.value))

    if not matches:
        return None

    matches.sort(key=lambda item: (-item[0], item[1].casefold()))
    return matches[0][1]


def _vocabulary_occurrences(
    query: str,
    values: Iterable[VocabularyInput],
) -> list[tuple[int, int, str]]:
    occurrences: list[tuple[int, int, str]] = []

    for raw_value in values:
        value = _as_vocabulary_value(raw_value)

        for phrase in [value.value, *value.aliases]:
            normalized_phrase = normalize_for_matching(phrase)

            if not normalized_phrase:
                continue

            pattern = rf"(?<![a-z0-9]){re.escape(normalized_phrase)}(?![a-z0-9])"

            for match in re.finditer(pattern, query):
                occurrences.append((match.start(), match.end(), value.value))

    unique_occurrences = list(dict.fromkeys(occurrences))
    unique_occurrences.sort(key=lambda item: (item[0], -(item[1] - item[0])))
    return unique_occurrences


def _extract_type_change(
    query: str,
    products: Iterable[VocabularyInput],
) -> tuple[str | None, str | None]:
    occurrences = _vocabulary_occurrences(query, products)

    if not occurrences:
        return None, None

    direction = re.search(r"\b(?:ke|to|menuju|menjadi)\b", query)

    if direction is None:
        return None, None

    before = [item for item in occurrences if item[1] <= direction.start()]
    after = [item for item in occurrences if item[0] >= direction.end()]

    if not before or not after:
        return None, None

    from_type = before[-1][2]
    to_type = after[0][2]

    if from_type == to_type:
        return None, None

    return from_type, to_type


def _extract_document_type(query: str) -> str | None:
    patterns = (
        (
            "retooling",
            r"\b(?:retooling|perubahan\s+(?:type|tipe)|ganti\s+(?:type|tipe))\b",
        ),
        ("axle_assy", r"\b(?:axle\s+(?:assy|assembly)|axle)\b"),
        ("brake_assy", r"\b(?:brake\s+(?:assy|assembly)|brake)\b"),
    )

    for document_type, pattern in patterns:
        if re.search(pattern, query):
            return document_type

    return None


def _extract_line(query: str, known_values: KnownValues) -> str | None:
    matched = _match_vocabulary(query, known_values.lines)

    if matched is not None:
        return matched

    if known_values.lines:
        return None

    generic_match = re.search(r"\bline\s+([123])\b", query)

    if generic_match is None:
        return None

    return f"LINE_{generic_match.group(1).upper()}"


def _extract_axle_type(query: str, known_values: KnownValues) -> str | None:
    # "Axle Assy" is both a document type phrase and a possible master value.
    # Require an explicit type marker so a general Axle query is not over-filtered.
    matched = _match_vocabulary(query, known_values.axle_types)

    if matched is None:
        return None

    if (
        normalize_for_matching(matched) == "axle assy"
        and re.search(r"\b(?:type|tipe)\b", query) is None
    ):
        return None

    return matched


def _extract_shift(query: str) -> int | None:
    match = re.search(
        r"\b(?:shift|shif)\s*(?:ke\s*)?([123]|satu|dua|tiga|one|two|three)\b",
        query,
    )

    if match is None:
        return None

    raw_value = match.group(1)
    return int(raw_value) if raw_value.isdigit() else NUMBER_WORDS[raw_value]


def _extract_month(query: str) -> int | None:
    for month_name, month_number in MONTHS.items():
        if re.search(rf"\b{re.escape(month_name)}\b", query):
            return month_number

    return None


def _extract_year(query: str) -> int | None:
    match = re.search(r"\b(19\d{2}|20\d{2}|2100)\b", query)
    return int(match.group(1)) if match else None


def _valid_date(year: int, month: int, day: int) -> date | None:
    try:
        return date(year, month, day)
    except ValueError:
        return None


def _extract_specific_date(query: str) -> date | None:
    iso_match = re.search(r"\b(19\d{2}|20\d{2}|2100)-(\d{1,2})-(\d{1,2})\b", query)

    if iso_match:
        return _valid_date(*(int(value) for value in iso_match.groups()))

    slash_match = re.search(r"\b(\d{1,2})[/-](\d{1,2})[/-](19\d{2}|20\d{2}|2100)\b", query)

    if slash_match:
        day, month, year = (int(value) for value in slash_match.groups())
        return _valid_date(year, month, day)

    month_names = "|".join(re.escape(name) for name in MONTHS)
    named_match = re.search(
        rf"\b(\d{{1,2}})\s+({month_names})\s+(19\d{{2}}|20\d{{2}}|2100)\b",
        query,
    )

    if named_match:
        day = int(named_match.group(1))
        month = MONTHS[named_match.group(2)]
        year = int(named_match.group(3))
        return _valid_date(year, month, day)

    return None


def extract_entities(
    query: str,
    known_values: KnownValues | None = None,
    reference_date: date | None = None,
) -> SearchEntities:
    """Extract exact business entities without inventing unknown values."""
    normalized_query = normalize_for_matching(query)
    vocabulary = known_values or KnownValues()
    today = reference_date or date.today()
    from_type, to_type = _extract_type_change(
        normalized_query,
        vocabulary.products,
    )
    document_type = _extract_document_type(normalized_query)

    if document_type is None and from_type is not None and to_type is not None:
        document_type = "retooling"
    month = _extract_month(normalized_query)
    year = _extract_year(normalized_query)
    date_query = unicodedata.normalize("NFKC", query).casefold()
    specific_date = _extract_specific_date(date_query)
    relative_date = None
    date_from = None
    date_to = None

    if "hari ini" in normalized_query or re.search(r"\btoday\b", normalized_query):
        relative_date = "today"
        date_from = today
        date_to = today
    elif "kemarin" in normalized_query or re.search(r"\byesterday\b", normalized_query):
        relative_date = "yesterday"
        date_from = today - timedelta(days=1)
        date_to = date_from
    elif "minggu lalu" in normalized_query or "previous week" in normalized_query:
        relative_date = "previous_week"
        # "Minggu lalu" means the previous calendar week, Monday through Sunday.
        date_from = today - timedelta(days=today.weekday() + 7)
        date_to = date_from + timedelta(days=6)
    elif specific_date is not None:
        date_from = specific_date
        date_to = specific_date
        month = specific_date.month
        year = specific_date.year
    elif month is not None and year is not None:
        date_from = date(year, month, 1)
        date_to = date(year, month, calendar.monthrange(year, month)[1])

    return SearchEntities(
        document_type=document_type,
        line=_extract_line(normalized_query, vocabulary),
        shift=_extract_shift(normalized_query),
        from_type=from_type,
        to_type=to_type,
        axle_type=_extract_axle_type(normalized_query, vocabulary),
        position=_match_vocabulary(normalized_query, vocabulary.positions),
        brake_part_number=_match_vocabulary(
            normalized_query,
            vocabulary.brake_part_numbers,
        ),
        date_from=date_from,
        date_to=date_to,
        month=month,
        year=year,
        relative_date=relative_date,
        sort=(
            "latest"
            if re.search(r"\b(?:terbaru|paling\s+baru|latest)\b", normalized_query)
            else None
        ),
    )
