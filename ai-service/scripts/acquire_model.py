from huggingface_hub import snapshot_download
import os

MODEL = 'intfloat/multilingual-e5-small'
DEST = r'D:\BMC_AI\Models\multilingual-e5-small'

os.makedirs(DEST, exist_ok=True)
print('Starting snapshot_download for', MODEL)
path = snapshot_download(repo_id=MODEL, local_dir=DEST, repo_type='model')
print('Downloaded to', path)
