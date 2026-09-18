import sys
from types import ModuleType

import numpy as np

from app.embedders import LocalSentenceTransformerEmbedder


def test_missing_local_model_does_not_try_to_load_remote(tmp_path) -> None:
    embedder = LocalSentenceTransformerEmbedder(
        model_path=tmp_path / "missing-model",
        model_label="local-test-model",
    )

    embedder.load()

    assert embedder.loaded is False
    assert embedder.load_error_code == "model_not_found"


def test_loader_forces_local_cpu_model_and_e5_prefixes(monkeypatch, tmp_path) -> None:
    calls = {"init": None, "encode": []}

    class FakeSentenceTransformer:
        def __init__(self, model_path, **kwargs) -> None:
            calls["init"] = (model_path, kwargs)

        def encode(self, texts, **kwargs):
            calls["encode"].append((texts, kwargs))
            return np.asarray([[1.0, 0.0] for _ in texts], dtype=np.float32)

    fake_module = ModuleType("sentence_transformers")
    fake_module.SentenceTransformer = FakeSentenceTransformer
    monkeypatch.setitem(sys.modules, "sentence_transformers", fake_module)

    model_path = tmp_path / "local-model"
    model_path.mkdir()
    embedder = LocalSentenceTransformerEmbedder(
        model_path=model_path,
        model_label="local-test-model",
    )

    embedder.load()
    query = embedder.encode_query("dokumen retooling")
    passages = embedder.encode_passages(["Jenis Dokumen: Retooling."])

    assert embedder.loaded is True
    assert calls["init"] == (
        str(model_path),
        {
            "device": "cpu",
            "local_files_only": True,
            "trust_remote_code": False,
        },
    )
    assert calls["encode"][0][0] == ["query: dokumen retooling"]
    assert calls["encode"][1][0] == ["passage: Jenis Dokumen: Retooling."]
    assert calls["encode"][0][1]["normalize_embeddings"] is True
    assert query.shape == (2,)
    assert passages.shape == (1, 2)

