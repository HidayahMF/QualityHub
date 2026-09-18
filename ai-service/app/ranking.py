from __future__ import annotations

import numpy as np

from .canonical import build_canonical_metadata
from .embedders import Embedder
from .schemas import CandidateDocument, SearchResult


def _normalize_vector(vector: np.ndarray) -> np.ndarray:
    flattened = np.asarray(vector, dtype=np.float32).reshape(-1)
    norm = float(np.linalg.norm(flattened))

    if not np.isfinite(norm) or norm == 0:
        raise ValueError("embedder returned an invalid query vector")

    return flattened / norm


def _normalize_matrix(matrix: np.ndarray, expected_rows: int) -> np.ndarray:
    values = np.asarray(matrix, dtype=np.float32)

    if values.ndim != 2 or values.shape[0] != expected_rows:
        raise ValueError("embedder returned an invalid passage matrix")

    norms = np.linalg.norm(values, axis=1, keepdims=True)

    if not np.all(np.isfinite(norms)) or np.any(norms == 0):
        raise ValueError("embedder returned an invalid passage vector")

    return values / norms


def rank_candidates(
    query: str,
    candidates: list[CandidateDocument],
    embedder: Embedder,
    top_k: int,
) -> list[SearchResult]:
    if not candidates:
        return []

    query_vector = _normalize_vector(embedder.encode_query(query))
    passage_texts = [build_canonical_metadata(candidate) for candidate in candidates]
    passage_vectors = _normalize_matrix(
        embedder.encode_passages(passage_texts),
        expected_rows=len(candidates),
    )

    if passage_vectors.shape[1] != query_vector.shape[0]:
        raise ValueError("query and passage vector dimensions do not match")

    # With normalized vectors, the dot product is cosine similarity.
    scores = np.clip(passage_vectors @ query_vector, -1.0, 1.0)
    ranked_indexes = sorted(
        range(len(candidates)),
        key=lambda index: (-float(scores[index]), candidates[index].document_id),
    )

    return [
        SearchResult(
            document_id=candidates[index].document_id,
            semantic_score=round(float(scores[index]), 6),
        )
        for index in ranked_indexes[: min(top_k, len(candidates))]
    ]

