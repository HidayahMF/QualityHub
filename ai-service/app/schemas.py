from __future__ import annotations

from datetime import date, datetime
from typing import Annotated, Literal

from pydantic import BaseModel, ConfigDict, Field, StringConstraints, model_validator


QueryText = Annotated[
    str,
    StringConstraints(strip_whitespace=True, min_length=1, max_length=500),
]
ShortText = Annotated[
    str,
    StringConstraints(strip_whitespace=True, min_length=1, max_length=255),
]
NullableShortTextValue = Annotated[
    str,
    StringConstraints(strip_whitespace=True, max_length=255),
]
OptionalShortText = NullableShortTextValue | None
MetadataText = Annotated[
    str,
    StringConstraints(strip_whitespace=True, max_length=2000),
]
OptionalMetadataText = MetadataText | None


class StrictModel(BaseModel):
    model_config = ConfigDict(
        extra="forbid",
        str_strip_whitespace=True,
        allow_inf_nan=False,
    )


class VocabularyValue(StrictModel):
    value: ShortText
    aliases: list[ShortText] = Field(default_factory=list, max_length=20)


VocabularyInput = VocabularyValue | ShortText


class KnownValues(StrictModel):
    lines: list[VocabularyInput] = Field(default_factory=list, max_length=250)
    products: list[VocabularyInput] = Field(default_factory=list, max_length=500)
    axle_types: list[VocabularyInput] = Field(default_factory=list, max_length=100)
    positions: list[VocabularyInput] = Field(default_factory=list, max_length=100)
    brake_part_numbers: list[VocabularyInput] = Field(
        default_factory=list,
        max_length=500,
    )


class ParseRequest(StrictModel):
    query: QueryText
    reference_date: date | None = None
    known_values: KnownValues = Field(default_factory=KnownValues)


class SearchEntities(StrictModel):
    document_type: Literal["retooling", "axle_assy", "brake_assy"] | None = None
    line: OptionalShortText = None
    shift: int | None = Field(default=None, ge=1, le=3)
    from_type: OptionalShortText = None
    to_type: OptionalShortText = None
    axle_type: OptionalShortText = None
    position: OptionalShortText = None
    brake_part_number: OptionalShortText = None
    date_from: date | None = None
    date_to: date | None = None
    month: int | None = Field(default=None, ge=1, le=12)
    year: int | None = Field(default=None, ge=1900, le=2100)
    relative_date: Literal["today", "yesterday", "previous_week"] | None = None
    sort: Literal["latest"] | None = None


class ParseResponse(StrictModel):
    query: str
    intent: Literal["search_document"] = "search_document"
    entities: SearchEntities


class CandidateDocument(StrictModel):
    document_id: int = Field(gt=0)
    title: ShortText
    file_name: ShortText
    document_code: OptionalShortText = None
    part_number: OptionalShortText = None
    keywords: OptionalMetadataText = None
    department: OptionalShortText = None
    category: OptionalShortText = None
    scan_type: OptionalShortText = None
    line: OptionalShortText = None
    shift: int | None = Field(default=None, ge=1, le=3)
    from_type: OptionalShortText = None
    to_type: OptionalShortText = None
    from_cycle_time: float | None = Field(default=None, ge=0)
    to_cycle_time: float | None = Field(default=None, ge=0)
    axle_type: OptionalShortText = None
    position: OptionalShortText = None
    brake_part_number: OptionalShortText = None
    document_date: date | None = None
    created_at: datetime | None = None
    updated_at: datetime | None = None


class SearchRequest(ParseRequest):
    candidates: list[CandidateDocument] = Field(max_length=500)
    top_k: int = Field(default=20, ge=1, le=50)

    @model_validator(mode="after")
    def candidate_ids_must_be_unique(self) -> "SearchRequest":
        document_ids = [candidate.document_id for candidate in self.candidates]

        if len(document_ids) != len(set(document_ids)):
            raise ValueError("candidate document_id values must be unique")

        return self


class SearchResult(StrictModel):
    document_id: int = Field(gt=0)
    semantic_score: float = Field(ge=-1, le=1)


class SearchResponse(ParseResponse):
    results: list[SearchResult]
    model: str
    mode: Literal["local"] = "local"


class HealthResponse(StrictModel):
    status: Literal["ok", "degraded"]
    mode: Literal["local"] = "local"
    model_loaded: bool
    model: str
    offline_mode: Literal[True] = True
    detail_code: str | None = None
