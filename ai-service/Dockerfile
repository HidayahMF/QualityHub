FROM python:3.11-slim

WORKDIR /app
ENV PYTHONUNBUFFERED=1 \
    TRANSFORMERS_OFFLINE=1 \
    HF_HUB_OFFLINE=1 \
    HF_HUB_DISABLE_TELEMETRY=1 \
    DO_NOT_TRACK=1 \
    TOKENIZERS_PARALLELISM=false \
    AI_MODEL_PATH=/models/multilingual-e5-small

COPY requirements.txt ./
# Use the CPU wheel so production does not download the multi-gigabyte CUDA build.
RUN pip install --no-cache-dir --extra-index-url https://download.pytorch.org/whl/cpu -r requirements.txt
COPY app ./app

EXPOSE 8765
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8765"]
