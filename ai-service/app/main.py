from __future__ import annotations

from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException, status

from .config import Settings
from .embedders import Embedder, LocalSentenceTransformerEmbedder
from .engine import SearchEngine
from .schemas import (
    HealthResponse,
    ParseRequest,
    ParseResponse,
    SearchRequest,
    SearchResponse,
)


def create_app(
    settings: Settings | None = None,
    embedder: Embedder | None = None,
) -> FastAPI:
    active_settings = settings or Settings.from_environment()
    active_embedder = embedder or LocalSentenceTransformerEmbedder(
        model_path=active_settings.model_path,
        model_label=active_settings.model_label,
    )
    engine = SearchEngine(active_settings, active_embedder)

    @asynccontextmanager
    async def lifespan(_: FastAPI):
        engine.load()
        yield

    application = FastAPI(
        title="BMC QualityHub Local AI Smart Search",
        version="1.0.0",
        docs_url=None,
        redoc_url=None,
        openapi_url=None,
        lifespan=lifespan,
    )

    @application.get("/health", response_model=HealthResponse)
    def health() -> HealthResponse:
        return HealthResponse(
            status="ok" if engine.embedder.loaded else "degraded",
            model_loaded=engine.embedder.loaded,
            model=engine.embedder.label,
            detail_code=engine.embedder.load_error_code,
        )

    @application.post("/parse", response_model=ParseResponse)
    def parse(request: ParseRequest) -> ParseResponse:
        return ParseResponse(
            query=request.query,
            entities=engine.parse(request),
        )

    @application.post("/search", response_model=SearchResponse)
    def search(request: SearchRequest) -> SearchResponse:
        if not engine.embedder.loaded:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail={
                    "code": engine.embedder.load_error_code or "model_unavailable",
                    "message": "Model AI lokal belum tersedia.",
                },
            )

        try:
            results = engine.search(request)
        except ValueError as error:
            if str(error) == "too_many_candidates":
                raise HTTPException(
                    status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                    detail={
                        "code": "too_many_candidates",
                        "message": (
                            "Jumlah kandidat melebihi batas service lokal "
                            f"({active_settings.max_candidates})."
                        ),
                    },
                ) from error

            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail={
                    "code": "embedding_failed",
                    "message": "Pemeringkatan semantic lokal gagal.",
                },
            ) from error
        except Exception as error:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail={
                    "code": "embedding_failed",
                    "message": "Pemeringkatan semantic lokal gagal.",
                },
            ) from error

        return SearchResponse(
            query=request.query,
            entities=engine.parse(request),
            results=results,
            model=engine.embedder.label,
        )

    return application


app = create_app()
