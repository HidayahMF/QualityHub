from __future__ import annotations

from abc import ABC, abstractmethod
from pathlib import Path
from threading import Lock

import numpy as np


class Embedder(ABC):
    @property
    @abstractmethod
    def label(self) -> str:
        raise NotImplementedError

    @property
    @abstractmethod
    def loaded(self) -> bool:
        raise NotImplementedError

    @property
    @abstractmethod
    def load_error_code(self) -> str | None:
        raise NotImplementedError

    @abstractmethod
    def load(self) -> None:
        raise NotImplementedError

    @abstractmethod
    def encode_query(self, query: str) -> np.ndarray:
        raise NotImplementedError

    @abstractmethod
    def encode_passages(self, passages: list[str]) -> np.ndarray:
        raise NotImplementedError


class LocalSentenceTransformerEmbedder(Embedder):
    def __init__(self, model_path: Path, model_label: str) -> None:
        self._model_path = model_path
        self._label = model_label
        self._model = None
        self._load_error_code: str | None = "model_not_loaded"
        self._encode_lock = Lock()

    @property
    def label(self) -> str:
        return self._label

    @property
    def loaded(self) -> bool:
        return self._model is not None

    @property
    def load_error_code(self) -> str | None:
        return self._load_error_code

    def load(self) -> None:
        if self.loaded:
            return

        if not self._model_path.is_dir():
            self._load_error_code = "model_not_found"
            return

        try:
            from sentence_transformers import SentenceTransformer

            self._model = SentenceTransformer(
                str(self._model_path),
                device="cpu",
                local_files_only=True,
                trust_remote_code=False,
            )
            self._load_error_code = None
        except Exception:
            # Health exposes only a safe code; absolute paths and internals stay in
            # the server process rather than being returned to the browser.
            self._model = None
            self._load_error_code = "model_load_failed"

    def encode_query(self, query: str) -> np.ndarray:
        return self._encode([f"query: {query}"])[0]

    def encode_passages(self, passages: list[str]) -> np.ndarray:
        return self._encode([f"passage: {passage}" for passage in passages])

    def _encode(self, texts: list[str]) -> np.ndarray:
        if self._model is None:
            raise RuntimeError("local model is not loaded")

        # Serialize inference to avoid duplicate CPU/RAM spikes on the 8 GB host.
        with self._encode_lock:
            vectors = self._model.encode(
                texts,
                batch_size=min(32, max(1, len(texts))),
                convert_to_numpy=True,
                normalize_embeddings=True,
                show_progress_bar=False,
            )
        return np.asarray(vectors, dtype=np.float32)
