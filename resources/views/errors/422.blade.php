<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tidak dapat diproses | Commissioning AKP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center p-4">
        <h1 class="h4 mb-2">Tidak dapat diproses</h1>
        <p class="text-muted mb-3">{{ $exception->getMessage() ?: 'Permintaan tidak dapat diproses. Coba lagi.' }}</p>
        <a href="{{ url('/commissioning') }}" class="btn btn-primary btn-sm">Ke dashboard</a>
    </div>
</body>
</html>
