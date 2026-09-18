import httpx

base = 'http://127.0.0.1:8765'

# Minimal candidate set to test real embedding ranking
candidates = [
    {
        'document_id': 1,
        'title': 'Instruksi Perubahan Type Retooling',
        'file_name': 'retooling.pdf',
        'document_code': 'RT-001',
        'part_number': None,
        'keywords': None,
        'department': None,
        'category': None,
        'scan_type': 'Retooling',
        'line': 'LINE_2',
        'shift': 1,
        'from_type': 'Product_A',
        'to_type': 'Product_E',
        'from_cycle_time': 25.0,
        'to_cycle_time': 22.0,
        'axle_type': None,
        'position': None,
        'brake_part_number': None,
        'document_date': None,
        'created_at': None,
        'updated_at': None,
    },
    {
        'document_id': 2,
        'title': 'Laporan Brake Assy',
        'file_name': 'brake.pdf',
        'document_code': 'BR-002',
        'part_number': None,
        'keywords': None,
        'department': None,
        'category': None,
        'scan_type': 'Laporan Hasil Produksi - Brake Assy',
        'line': 'LINE_1',
        'shift': 1,
        'from_type': None,
        'to_type': None,
        'from_cycle_time': None,
        'to_cycle_time': None,
        'axle_type': None,
        'position': None,
        'brake_part_number': '402 / 502',
        'document_date': None,
        'created_at': None,
        'updated_at': None,
    },
]

payload = {
    'query': 'cari dokumen retooling line 2 perubahan product A ke product E',
    'reference_date': None,
    'known_values': {},
    'candidates': candidates,
    'top_k': 2,
}

with httpx.Client(timeout=30) as c:
    r = c.post(f"{base}/search", json=payload)
    print('status', r.status_code)
    print(r.json())
