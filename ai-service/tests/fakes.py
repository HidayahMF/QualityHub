from __future__ import annotations

import numpy as np

from app.embedders import Embedder


class FakeEmbedder(Embedder):
    def __init__(self) -> None:
        self._loaded = False

    @property
    def label(self) -> str:
        return "fake-test-embedder"

    @property
    def loaded(self) -> bool:
        return self._loaded

    @property
    def load_error_code(self) -> str | None:
        return None if self.loaded else "model_not_loaded"

    def load(self) -> None:
        self._loaded = True

    def encode_query(self, query: str) -> np.ndarray:
        return np.array([1.0, 0.0], dtype=np.float32)

    def encode_passages(self, passages: list[str]) -> np.ndarray:
        vectors = []

        for passage in passages:
            if "LINE_2" in passage or "product_E" in passage:
                vectors.append([1.0, 0.0])
            else:
                vectors.append([0.0, 1.0])

        return np.asarray(vectors, dtype=np.float32)


class UnavailableEmbedder(FakeEmbedder):
    @property
    def load_error_code(self) -> str | None:
        return "model_not_found"

    def load(self) -> None:
        self._loaded = False

