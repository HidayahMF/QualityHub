import httpx
import time

base = "http://127.0.0.1:8765"

with httpx.Client(timeout=10) as c:
    try:
        r = c.get(f"{base}/health")
        print('GET /health', r.status_code, r.json())
    except Exception as e:
        print('health error', e)

    try:
        payload = {
            'query': 'cari dokumen retooling line 2 bulan september',
            'reference_date': None,
            'known_values': {}
        }
        r = c.post(f"{base}/parse", json=payload)
        print('POST /parse', r.status_code, r.json())
    except Exception as e:
        print('parse error', e)
