from app.canonical import build_canonical_metadata
from app.ranking import rank_candidates
from app.schemas import CandidateDocument

from .fakes import FakeEmbedder


def test_canonical_metadata_contains_only_supplied_fields() -> None:
    candidate = CandidateDocument(
        document_id=16,
        title="INSTRUKSI PERUBAHAN TYPE (RETOOLING)",
        file_name="instruksi-perubahan-type-retooling.pdf",
        scan_type="Retooling",
        keywords="perubahan type",
        line="LINE_2",
        from_type="product_A",
        to_type="product_E",
    )

    text = build_canonical_metadata(candidate)

    assert "Jenis Dokumen: Retooling" in text
    assert "Line: LINE_2" in text
    assert "Dari Type: product_A" in text
    assert "Kata Kunci: perubahan type" in text
    assert "Posisi:" not in text
    assert "document_id" not in text


def test_cosine_ranking_is_deterministic_with_fake_embedder() -> None:
    candidates = [
        CandidateDocument(
            document_id=9,
            title="Dokumen umum",
            file_name="umum.pdf",
        ),
        CandidateDocument(
            document_id=16,
            title="Retooling",
            file_name="retooling.pdf",
            line="LINE_2",
        ),
    ]

    results = rank_candidates(
        query="retooling line 2",
        candidates=candidates,
        embedder=FakeEmbedder(),
        top_k=2,
    )

    assert [result.document_id for result in results] == [16, 9]
    assert results[0].semantic_score == 1.0
    assert results[1].semantic_score == 0.0
