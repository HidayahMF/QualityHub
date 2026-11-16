from datetime import date

from app.entities import extract_entities
from app.schemas import KnownValues, VocabularyValue


def test_extracts_retooling_line_and_month() -> None:
    entities = extract_entities(
        "cari dokumen retooling line 2 bulan september",
        reference_date=date(2026, 9, 17),
    )

    assert entities.document_type == "retooling"
    assert entities.line == "LINE_2"
    assert entities.month == 9
    assert entities.year is None
    assert entities.date_from is None


def test_day_month_without_year_is_exact_day_across_years() -> None:
    entities = extract_entities(
        "cari dokumen retooling tanggal 12 november",
        reference_date=date(2026, 11, 16),
    )

    assert entities.day == 12
    assert entities.month == 11
    assert entities.year is None
    assert entities.date_from is None
    assert entities.date_to is None


def test_numeric_day_month_with_explicit_marker() -> None:
    entities = extract_entities(
        "cari dokumen tanggal 12/11",
        reference_date=date(2026, 11, 16),
    )

    assert entities.day == 12
    assert entities.month == 11
    assert entities.year is None
    assert entities.date_from is None
    assert entities.date_to is None


def test_day_month_year_is_an_exact_date_even_with_bulan_keyword() -> None:
    entities = extract_entities(
        "dokumen tanggal 12 bulan November tahun 2026",
        reference_date=date(2026, 11, 16),
    )

    assert entities.day == 12
    assert entities.month == 11
    assert entities.year == 2026
    assert entities.date_from == date(2026, 11, 12)
    assert entities.date_to == date(2026, 11, 12)


def test_existing_full_date_formats_still_match_one_day() -> None:
    queries = [
        "dokumen 12 November 2026",
        "dokumen 2026-11-12",
        "dokumen 12/11/2026",
    ]
    for query in queries:
        entities = extract_entities(query, reference_date=date(2026, 11, 16))
        assert entities.day == 12
        assert entities.month == 11
        assert entities.year == 2026
        assert entities.date_from == date(2026, 11, 12)
        assert entities.date_to == date(2026, 11, 12)


def test_february_29_without_year_can_search_past_leap_years() -> None:
    entities = extract_entities("tanggal 29 Februari")
    assert entities.day == 29
    assert entities.month == 2
    assert entities.year is None
    assert entities.date_from is None


def test_month_only_and_relative_dates_not_reinterpreted_as_specific_day() -> None:
    month = extract_entities("cari dokumen bulan november")
    today = extract_entities("cari dokumen hari ini", reference_date=date(2026, 11, 16))

    assert month.day is None
    assert month.month == 11
    assert month.year is None
    assert today.day is None
    assert today.date_from == date(2026, 11, 16)
    assert today.date_to == date(2026, 11, 16)


def test_extracts_database_product_values_without_inventing_names() -> None:
    known_values = KnownValues(
        products=[
            VocabularyValue(value="product_A", aliases=["product A"]),
            VocabularyValue(value="product_E", aliases=["product E"]),
        ]
    )

    entities = extract_entities(
        "cari dokumen perubahan product A ke product E",
        known_values=known_values,
    )

    assert entities.document_type == "retooling"
    assert entities.from_type == "product_A"
    assert entities.to_type == "product_E"


def test_extracts_brake_part_number_from_database_alias() -> None:
    known_values = KnownValues(
        brake_part_numbers=[
            VocabularyValue(
                value="A 4304200 402 / 502",
                aliases=["402 / 502", "402 502"],
            )
        ]
    )

    entities = extract_entities(
        "dokumen brake assy part number 402 502",
        known_values=known_values,
    )

    assert entities.document_type == "brake_assy"
    assert entities.brake_part_number == "A 4304200 402 / 502"


def test_extracts_axle_position_and_latest_sort() -> None:
    known_values = KnownValues(
        positions=[VocabularyValue(value="LH")],
    )

    entities = extract_entities(
        "cari axle assy posisi LH terbaru",
        known_values=known_values,
    )

    assert entities.document_type == "axle_assy"
    assert entities.position == "LH"
    assert entities.sort == "latest"


def test_previous_week_is_previous_monday_through_sunday() -> None:
    entities = extract_entities(
        "dokumen perubahan tipe minggu lalu",
        reference_date=date(2026, 9, 17),
    )

    assert entities.relative_date == "previous_week"
    assert entities.date_from == date(2026, 9, 7)
    assert entities.date_to == date(2026, 9, 13)


def test_specific_month_and_year_become_complete_date_range() -> None:
    entities = extract_entities("dokumen september 2026")

    assert entities.month == 9
    assert entities.year == 2026
    assert entities.date_from == date(2026, 9, 1)
    assert entities.date_to == date(2026, 9, 30)


def test_extracts_shift_and_relative_days() -> None:
    today = extract_entities(
        "dokumen shift dua hari ini",
        reference_date=date(2026, 9, 17),
    )
    yesterday = extract_entities(
        "dokumen kemarin",
        reference_date=date(2026, 9, 17),
    )

    assert today.shift == 2
    assert today.relative_date == "today"
    assert today.date_from == date(2026, 9, 17)
    assert today.date_to == date(2026, 9, 17)
    assert yesterday.relative_date == "yesterday"
    assert yesterday.date_from == date(2026, 9, 16)
    assert yesterday.date_to == date(2026, 9, 16)


def test_unknown_business_entity_is_not_invented() -> None:
    entities = extract_entities(
        "cari dokumen produk yang belum dikenal line 99",
        known_values=KnownValues(
            lines=[VocabularyValue(value="LINE_1", aliases=["line 1"])]
        ),
    )

    assert entities.from_type is None
    assert entities.to_type is None
    assert entities.axle_type is None
    assert entities.position is None
    assert entities.brake_part_number is None
    assert entities.line is None

    without_vocabulary = extract_entities("cari line 999")
    assert without_vocabulary.line is None


def test_axle_document_phrase_does_not_implicitly_become_axle_type() -> None:
    known_values = KnownValues(
        axle_types=[
            VocabularyValue(value="Module Assy"),
            VocabularyValue(value="Axle Assy"),
        ]
    )

    general = extract_entities("axle assy posisi LH", known_values=known_values)
    explicit = extract_entities(
        "axle assy type Module Assy",
        known_values=known_values,
    )

    assert general.document_type == "axle_assy"
    assert general.axle_type is None
    assert explicit.axle_type == "Module Assy"

    module_without_marker = extract_entities(
        "dokumen Module Assy terbaru",
        known_values=known_values,
    )
    assert module_without_marker.axle_type == "Module Assy"
