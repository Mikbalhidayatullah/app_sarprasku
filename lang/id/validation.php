<?php

return [
    'required' => ':attribute wajib diisi.', 'required_if' => ':attribute wajib diisi untuk :other = :value.', 'string' => ':attribute harus berupa teks.', 'email' => 'Format :attribute tidak valid.',
    'unique' => ':attribute sudah digunakan.', 'exists' => ':attribute tidak tersedia.', 'in' => ':attribute tidak sesuai pilihan yang diizinkan.',
    'integer' => ':attribute harus berupa bilangan bulat.', 'numeric' => ':attribute harus berupa angka.', 'boolean' => ':attribute harus ya atau tidak.',
    'date_format' => 'Format :attribute harus :format.', 'date' => ':attribute harus tanggal yang valid.', 'before_or_equal' => ':attribute tidak boleh setelah :date.',
    'after_or_equal' => ':attribute harus sama atau setelah :date.', 'decimal' => ':attribute harus desimal dengan :decimal angka di belakang koma.',
    'confirmed' => 'Konfirmasi :attribute tidak sama.', 'uuid' => ':attribute tidak valid.', 'array' => ':attribute harus berupa daftar.', 'regex' => 'Format :attribute tidak valid.',
    'min' => ['numeric' => ':attribute minimal :min.', 'string' => ':attribute minimal :min karakter.', 'array' => ':attribute minimal :min item.', 'file' => ':attribute minimal :min KB.'],
    'max' => ['numeric' => ':attribute maksimal :max.', 'string' => ':attribute maksimal :max karakter.', 'array' => ':attribute maksimal :max item.', 'file' => ':attribute maksimal :max KB.'],
    'mimes' => ':attribute hanya menerima berkas :values.', 'mimetypes' => ':attribute harus bertipe :values.', 'extensions' => 'Ekstensi :attribute hanya :values.',
    'uploaded' => 'Unggahan :attribute gagal.', 'file' => ':attribute harus berkas valid.', 'timezone' => ':attribute harus zona waktu IANA yang valid.',
    'password' => ['letters' => ':attribute harus berisi huruf.', 'numbers' => ':attribute harus berisi angka.'],
    'attributes' => ['name' => 'Nama', 'inventory_number' => 'Nomor inventaris', 'ownership_id' => 'Status kepemilikan', 'classification_id' => 'Klasifikasi', 'email' => 'Email', 'password' => 'Password', 'role_id' => 'Peran', 'is_active' => 'Status aktif', 'file' => 'Berkas', 'effective_on' => 'Tanggal efektif', 'to_room_id' => 'Ruangan tujuan', 'reason' => 'Alasan', 'allocations' => 'Alokasi pendanaan', 'disposal_reference' => 'Referensi dokumen pelepasan', 'condition' => 'Kondisi', 'decision_note' => 'Alasan keputusan', 'inspected_on' => 'Tanggal pemeriksaan', 'performed_on' => 'Tanggal pemeliharaan'],
];
