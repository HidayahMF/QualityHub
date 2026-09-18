param(
    [string]$ModelPath = $env:AI_MODEL_PATH,
    [int]$Port = 8765
)

$serviceRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$pythonPath = Join-Path $serviceRoot '.venv\Scripts\python.exe'

if (-not (Test-Path -LiteralPath $pythonPath -PathType Leaf)) {
    throw 'Virtual environment belum tersedia. Ikuti langkah setup di README.md.'
}

if ([string]::IsNullOrWhiteSpace($ModelPath)) {
    $ModelPath = 'D:\BMC_AI\Models\multilingual-e5-small'
}

$env:AI_MODEL_PATH = $ModelPath
$env:TRANSFORMERS_OFFLINE = '1'
$env:HF_HUB_OFFLINE = '1'
$env:HF_HUB_DISABLE_TELEMETRY = '1'
$env:DO_NOT_TRACK = '1'
$env:TOKENIZERS_PARALLELISM = 'false'

Set-Location -LiteralPath $serviceRoot
& $pythonPath -m uvicorn app.main:app --host 127.0.0.1 --port $Port
exit $LASTEXITCODE

