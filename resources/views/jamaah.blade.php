<form action="{{ route('proses-upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label>File Excel Jamaah</label>
        <input type="file" name="file_excel" accept=".xlsx,.xls" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Proses & Download</button>
</form>