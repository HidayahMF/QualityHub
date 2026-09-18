from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path


def enforce_offline_environment() -> None:
    """Force offline flags before transformers or Hugging Face is imported."""
    os.environ["TRANSFORMERS_OFFLINE"] = "1"
    os.environ["HF_HUB_OFFLINE"] = "1"
    os.environ["HF_HUB_DISABLE_TELEMETRY"] = "1"
    os.environ["DO_NOT_TRACK"] = "1"
    os.environ["TOKENIZERS_PARALLELISM"] = "false"


# A pre-existing `0` must not weaken the production offline guarantee.
enforce_offline_environment()


def _integer_setting(name: str, default: int, minimum: int, maximum: int) -> int:
    raw_value = os.getenv(name)

    if raw_value is None:
        return default

    try:
        value = int(raw_value)
    except ValueError:
        return default

    return min(maximum, max(minimum, value))


@dataclass(frozen=True, slots=True)
class Settings:
    model_path: Path
    model_label: str = "intfloat/multilingual-e5-small"
    max_candidates: int = 250

    @classmethod
    def from_environment(cls) -> "Settings":
        return cls(
            model_path=Path(
                os.getenv(
                    "AI_MODEL_PATH",
                    r"D:\BMC_AI\Models\multilingual-e5-small",
                )
            ),
            model_label=os.getenv(
                "AI_MODEL_LABEL",
                "intfloat/multilingual-e5-small",
            ),
            max_candidates=_integer_setting(
                "AI_MAX_CANDIDATES",
                default=250,
                minimum=1,
                maximum=500,
            ),
        )
