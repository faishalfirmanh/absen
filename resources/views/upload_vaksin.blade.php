<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Data Vaksin Jamaah</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Upload Data Vaksin Jamaah</h5>
        </div>
        <div class="card-body">
            <form id="formUploadVaksin" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="file">File Excel (.xlsx / .xls)</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.ods">
                </div>

                <button type="submit" class="btn btn-primary" id="btnUpload">
                    <span id="btnUploadText">Upload</span>
                    <span id="btnUploadSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    $('#formUploadVaksin').on('submit', function (e) {
        e.preventDefault();

        const fileInput = $('#file')[0];
        if (!fileInput.files.length) {
            Swal.fire({
                icon: 'warning',
                title: 'File belum dipilih',
                text: 'Silakan pilih file excel terlebih dahulu.',
            });
            return;
        }

        const formData = new FormData(this);

        $('#btnUpload').prop('disabled', true);
        $('#btnUploadText').text('Mengupload...');
        $('#btnUploadSpinner').removeClass('d-none');

        $.ajax({
            url: "{{ route('up_v') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                // Kalau route api.php pakai Bearer token, ganti/tambahkan baris di atas dengan:
                // 'Authorization': 'Bearer ' + yourToken
            },
            success: function (res) {
                let skippedInfo = '';
                if (res.data && res.data.skipped && res.data.skipped.length) {
                    skippedInfo = `<br><small>${res.data.skipped.length} baris dilewati karena tidak valid.</small>`;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    html: (res.message || 'Data berhasil diupload.') + skippedInfo,
                });

                $('#formUploadVaksin')[0].reset();
            },
            error: function (xhr) {
                const res = xhr.responseJSON;
                let errorText = res && res.message ? res.message : 'Terjadi kesalahan saat upload.';

                if (res && res.errors) {
                    let list = '<ul style="text-align:left; margin-top:10px;">';

                    if (Array.isArray(res.errors)) {
                        // Bentuk: [{ row: 2, errors: ["pesan1", "pesan2"] }, ...]
                        res.errors.forEach(function (item) {
                            if (item.row !== undefined && Array.isArray(item.errors)) {
                                item.errors.forEach(function (msg) {
                                    list += `<li>Baris ${item.row}: ${msg}</li>`;
                                });
                            }
                        });
                    } else if (typeof res.errors === 'object') {
                        // Bentuk: { file: ["pesan1", ...] } (dari Validator::errors() level file)
                        $.each(res.errors, function (field, messages) {
                            if (Array.isArray(messages)) {
                                messages.forEach(function (msg) {
                                    list += `<li>${msg}</li>`;
                                });
                            }
                        });
                    }

                    list += '</ul>';
                    errorText += list;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: errorText,
                });
            },
            complete: function () {
                $('#btnUpload').prop('disabled', false);
                $('#btnUploadText').text('Upload');
                $('#btnUploadSpinner').addClass('d-none');
            }
        });
    });
});
</script>

</body>
</html>