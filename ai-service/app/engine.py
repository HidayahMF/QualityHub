from __future__ import annotations

from .config import Settings
from .embedders import Embedder
from .entities import extract_entities
from .ranking import rank_candidates
from .schemas import ParseRequest, SearchEntities, SearchRequest, SearchResult


class SearchEngine:
    def __init__(self, settings: Settings, embedder: Embedder) -> None:
        self.settings = settings
        self.embedder = embedder

    def load(self) -> None:
        self.embedder.load()

    def parse(self, request: ParseRequest) -> SearchEntities:
        return extract_entities(
            request.query,
            known_values=request.known_values,
            reference_date=request.reference_date,
        )

    def search(self, request: SearchRequest) -> list[SearchResult]:
        if len(request.candidates) > self.settings.max_candidates:
            raise ValueError("too_many_candidates")

        entities = self.parse(request)
        requested_results = (
            len(request.candidates)
            if entities.sort == "latest"
            else request.top_k
        )
        results = rank_candidates(
            query=request.query,
            candidates=request.candidates,
            embedder=self.embedder,
            top_k=requested_results,
        )

        if entities.sort != "latest":
            return results

        candidates_by_id = {
            candidate.document_id: candidate for candidate in request.candidates
        }

        def latest_key(result: SearchResult) -> tuple[int, float, float, int]:
            candidate = candidates_by_id[result.document_id]
            relevant_date = candidate.document_date

            if relevant_date is None and candidate.created_at is not None:
                relevant_date = candidate.created_at.date()

            return (
                relevant_date.toordinal() if relevant_date is not None else 0,
                candidate.created_at.timestamp()
                if candidate.created_at is not None
                else 0.0,
                result.semantic_score,
                -result.document_id,
            )

        results.sort(key=latest_key, reverse=True)
        return results[: request.top_k]
