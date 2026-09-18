from pathlib import Path

from fastapi.testclient import TestClient

from app.config import Settings
from app.main import create_app

from .fakes import FakeEmbedder, UnavailableEmbedder


def test_health_reports_local_loaded_model() -> None:
    app = create_app(
        settings=Settings(model_path=Path("unused")),
        embedder=FakeEmbedder(),
    )

    with TestClient(app) as client:
        response = client.get("/health")

    assert response.status_code == 200
    assert response.json() == {
        "status": "ok",
        "mode": "local",
        "model_loaded": True,
        "model": "fake-test-embedder",
        "offline_mode": True,
        "detail_code": None,
    }


def test_parse_does_not_require_embedding_model() -> None:
    app = create_app(
        settings=Settings(model_path=Path("missing")),
        embedder=UnavailableEmbedder(),
    )

    with TestClient(app) as client:
        response = client.post(
            "/parse",
            json={
                "query": "retooling line 2 bulan september",
                "reference_date": "2026-09-17",
            },
        )

    assert response.status_code == 200
    assert response.json()["entities"]["document_type"] == "retooling"
    assert response.json()["entities"]["line"] == "LINE_2"


def test_search_returns_only_ranked_candidate_ids_and_scores() -> None:
    app = create_app(
        settings=Settings(model_path=Path("unused")),
        embedder=FakeEmbedder(),
    )
    payload = {
        "query": "cari retooling line 2",
        "candidates": [
            {
                "document_id": 9,
                "title": "Dokumen umum",
                "file_name": "umum.pdf",
            },
            {
                "document_id": 16,
                "title": "Retooling",
                "file_name": "retooling.pdf",
                "scan_type": "Retooling",
                "line": "LINE_2",
            },
        ],
        "top_k": 2,
    }

    with TestClient(app) as client:
        response = client.post("/search", json=payload)

    assert response.status_code == 200
    body = response.json()
    assert body["mode"] == "local"
    assert body["model"] == "fake-test-embedder"
    assert body["results"] == [
        {"document_id": 16, "semantic_score": 1.0},
        {"document_id": 9, "semantic_score": 0.0},
    ]


def test_latest_uses_document_date_instead_of_semantic_score() -> None:
    app = create_app(
        settings=Settings(model_path=Path("unused")),
        embedder=FakeEmbedder(),
    )
    payload = {
        "query": "dokumen terbaru",
        "candidates": [
            {
                "document_id": 1,
                "title": "LINE_2 semantic match",
                "file_name": "lama.pdf",
                "line": "LINE_2",
                "document_date": "2026-01-01",
            },
            {
                "document_id": 2,
                "title": "Dokumen baru",
                "file_name": "baru.pdf",
                "document_date": "2026-09-17",
            },
        ],
        "top_k": 2,
    }

    with TestClient(app) as client:
        response = client.post("/search", json=payload)

    assert response.status_code == 200
    assert [item["document_id"] for item in response.json()["results"]] == [2, 1]


def test_search_rejects_empty_and_oversized_queries() -> None:
    app = create_app(
        settings=Settings(model_path=Path("unused")),
        embedder=FakeEmbedder(),
    )

    with TestClient(app) as client:
        empty = client.post("/search", json={"query": " ", "candidates": []})
        oversized = client.post(
            "/search",
            json={"query": "x" * 501, "candidates": []},
        )

    assert empty.status_code == 422
    assert oversized.status_code == 422


def test_search_rejects_malformed_or_duplicate_candidates() -> None:
    app = create_app(
        settings=Settings(model_path=Path("unused")),
        embedder=FakeEmbedder(),
    )

    with TestClient(app) as client:
        malformed = client.post(
            "/search",
            json={
                "query": "dokumen",
                "candidates": [{"document_id": -1, "title": "A"}],
            },
        )
        duplicate = client.post(
            "/search",
            json={
                "query": "dokumen",
                "candidates": [
                    {"document_id": 1, "title": "A", "file_name": "a.pdf"},
                    {"document_id": 1, "title": "B", "file_name": "b.pdf"},
                ],
            },
        )
        forbidden_extra = client.post(
            "/search",
            json={
                "query": "dokumen",
                "candidates": [
                    {
                        "document_id": 2,
                        "title": "Dokumen",
                        "file_name": "dokumen.pdf",
                        "file_path": "D:/private/document.pdf",
                    }
                ],
            },
        )

    assert malformed.status_code == 422
    assert duplicate.status_code == 422
    assert forbidden_extra.status_code == 422


def test_search_fails_clearly_when_local_model_is_missing() -> None:
    app = create_app(
        settings=Settings(model_path=Path("missing")),
        embedder=UnavailableEmbedder(),
    )

    with TestClient(app) as client:
        health = client.get("/health")
        response = client.post(
            "/search",
            json={"query": "dokumen retooling", "candidates": []},
        )

    assert health.json()["model_loaded"] is False
    assert health.json()["detail_code"] == "model_not_found"
    assert response.status_code == 503
    assert response.json()["detail"]["code"] == "model_not_found"
