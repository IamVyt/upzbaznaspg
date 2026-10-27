<?php

return [
    'site' => [
        'nama' => 'UPZ BAZNAS PG',
        'lengkap' => 'UPZ BAZNAS Petrokimia Gresik',
        'email' => 'upzpkg@gmail.com',
        'telp' => '(031) 3973959',
        'alamat' => 'Jl. Jenderal Ahmad Yani 07 Gresik, Jawa Timur',
        'rekening' => ['bank' => 'BSI Syariah', 'nomor' => '2020300041', 'atas_nama' => 'UPZ Baznas Petrokimia Gresik'],
        'wa' => '082264904535',
        'wa_link' => 'https://wa.me/6282264904535',
        'peta' => 'https://www.google.com/maps?q=UPZ+BAZNAS+Petrokimia+Gresik,+Jl.+Jenderal+Ahmad+Yani+No.03,+Gresik&output=embed',
        'sosial' => [
            'Facebook' => ['#', 'i-fb'],
            'Instagram' => ['#', 'i-ig'],
            'YouTube' => ['#', 'i-yt'],
        ],
    ],

    'mitra' => ['BAZNAS', 'Petrokimia Gresik', 'Pupuk Indonesia', 'Kementerian Agama'],

    'program' => [
        [
            'nama' => 'Zakat penghasilan',
            'slug' => 'zakat-penghasilan',
            'warna' => 'l',
            'ringkas' => 'Zakat penghasilan adalah zakat yang dikeluarkan dari gaji, honor, atau pendapatan lain yang diterima secara rutin. Dana yang terkumpul disalurkan kepada golongan yang berhak menerima zakat.',
            'poin' => [
                'Kadar zakat penghasilan umumnya 2,5 persen dari penghasilan yang sudah mencapai nisab.',
                'Besaran nisab mengikuti ketetapan BAZNAS yang berlaku, jadi cek angka terbarunya sebelum menghitung.',
                'Bisa ditunaikan setiap bulan atau sekaligus setahun, sesuai kemudahan masing-masing.',
                'Klik Salurkan sekarang untuk memilih paket dan membayar, atau hubungi pengurus UPZ bila ada pertanyaan.',
            ],
        ],
        [
            'nama' => 'Infak dan sedekah',
            'slug' => 'infak-dan-sedekah',
            'warna' => 'g',
            'ringkas' => 'Infak dan sedekah adalah pemberian sukarela di luar zakat. Tidak ada batas minimal maupun waktu tertentu, sehingga siapa pun bisa berbagi sesuai kemampuan.',
            'poin' => [
                'Tidak ada nisab dan kadar tertentu, nominalnya bebas.',
                'Dapat diberikan kapan saja, tidak harus menunggu waktu tertentu.',
                'Dana digunakan untuk program sosial yang dikelola UPZ. Rinciannya bisa ditanyakan ke pengurus.',
            ],
        ],
        [
            'nama' => 'Bantuan pendidikan',
            'slug' => 'bantuan-pendidikan',
            'warna' => 'd',
            'ringkas' => 'Program ini membantu biaya pendidikan bagi anak dan pelajar dari keluarga kurang mampu agar mereka tetap bisa bersekolah.',
            'poin' => [
                'Sasaran: pelajar dan mahasiswa dari keluarga kurang mampu.',
                'Contoh bentuk bantuan: biaya sekolah, perlengkapan belajar, atau beasiswa.',
                'Syarat dan jadwal pengajuan ditetapkan pengurus UPZ.',
            ],
        ],
        [
            'nama' => 'Layanan kesehatan',
            'slug' => 'layanan-kesehatan',
            'warna' => 'l',
            'ringkas' => 'Program ini membantu warga yang kesulitan membiayai pengobatan dan layanan kesehatan dasar.',
            'poin' => [
                'Sasaran: warga kurang mampu yang membutuhkan bantuan kesehatan.',
                'Contoh bentuk bantuan: biaya berobat, obat, atau layanan kesehatan gratis.',
                'Syarat dan cara pengajuan ditetapkan pengurus UPZ.',
            ],
        ],
        [
            'nama' => 'Pemberdayaan ekonomi',
            'slug' => 'pemberdayaan-ekonomi',
            'warna' => 'g',
            'ringkas' => 'Program ini mendukung warga prasejahtera membangun atau mengembangkan usaha kecil agar penghasilan keluarga lebih stabil.',
            'poin' => [
                'Sasaran: pelaku usaha kecil dan keluarga prasejahtera.',
                'Contoh bentuk bantuan: modal usaha, peralatan, atau pelatihan.',
                'Syarat dan cara pengajuan ditetapkan pengurus UPZ.',
            ],
        ],
        [
            'nama' => 'Bantuan kemanusiaan',
            'slug' => 'bantuan-kemanusiaan',
            'warna' => 'd',
            'ringkas' => 'Program ini memberi bantuan cepat bagi warga yang terdampak bencana atau kondisi darurat.',
            'poin' => [
                'Sasaran: warga yang terdampak bencana atau musibah.',
                'Contoh bentuk bantuan: kebutuhan pokok, tempat tinggal sementara, atau perlengkapan darurat.',
                'Syarat dan cara pengajuan ditetapkan pengurus UPZ.',
            ],
        ],
    ],

    'penyaluran' => [
        'zakat-penghasilan' => [
            'kategori' => 'ZAKAT_MAAL',
            'paket' => [
                ['nama' => 'Zakat bulanan', 'nominal' => 0, 'ket' => 'Tunaikan zakat penghasilan setiap bulan sesuai perhitungan Anda.', 'hitung' => true],
                ['nama' => 'Zakat tahunan', 'nominal' => 0, 'ket' => 'Tunaikan zakat penghasilan satu tahun sekaligus.', 'hitung' => true],
            ],
        ],
        'infak-dan-sedekah' => [
            'kategori' => 'INFAK',
            'paket' => [
                ['nama' => 'Paket Berbagi', 'nominal' => 50000, 'ket' => 'Infak ringan untuk mendukung program sosial UPZ.'],
                ['nama' => 'Paket Peduli', 'nominal' => 100000, 'ket' => 'Sedekah untuk membantu lebih banyak penerima manfaat.'],
                ['nama' => 'Paket Berkah', 'nominal' => 250000, 'ket' => 'Infak untuk memperkuat seluruh program UPZ.'],
                ['nama' => 'Nominal bebas', 'nominal' => 0, 'ket' => 'Tentukan sendiri nominal infak atau sedekah Anda.'],
            ],
        ],
        'bantuan-pendidikan' => [
            'kategori' => 'DANA_SOSIAL',
            'paket' => [
                ['nama' => 'Perlengkapan sekolah', 'nominal' => 150000, 'ket' => 'Membantu perlengkapan belajar bagi pelajar kurang mampu.'],
                ['nama' => 'Biaya sekolah satu bulan', 'nominal' => 300000, 'ket' => 'Membantu biaya sekolah seorang pelajar selama satu bulan.'],
                ['nama' => 'Beasiswa satu semester', 'nominal' => 1500000, 'ket' => 'Membantu biaya pendidikan seorang pelajar selama satu semester.'],
                ['nama' => 'Nominal bebas', 'nominal' => 0, 'ket' => 'Tentukan sendiri nominal bantuan pendidikan Anda.'],
            ],
        ],
        'layanan-kesehatan' => [
            'kategori' => 'DANA_SOSIAL',
            'paket' => [
                ['nama' => 'Obat dan vitamin', 'nominal' => 100000, 'ket' => 'Membantu kebutuhan obat dan vitamin bagi warga kurang mampu.'],
                ['nama' => 'Biaya berobat', 'nominal' => 500000, 'ket' => 'Membantu biaya berobat warga yang kesulitan membayar pengobatan.'],
                ['nama' => 'Layanan kesehatan gratis', 'nominal' => 1000000, 'ket' => 'Mendukung layanan kesehatan gratis bagi warga sekitar.'],
                ['nama' => 'Nominal bebas', 'nominal' => 0, 'ket' => 'Tentukan sendiri nominal bantuan kesehatan Anda.'],
            ],
        ],
        'pemberdayaan-ekonomi' => [
            'kategori' => 'DANA_SOSIAL',
            'paket' => [
                ['nama' => 'Peralatan usaha kecil', 'nominal' => 500000, 'ket' => 'Membantu pelaku usaha kecil melengkapi peralatan usahanya.'],
                ['nama' => 'Modal usaha', 'nominal' => 1000000, 'ket' => 'Membantu modal usaha bagi keluarga prasejahtera.'],
                ['nama' => 'Pelatihan usaha', 'nominal' => 2000000, 'ket' => 'Mendukung pelatihan agar usaha kecil lebih berkembang.'],
                ['nama' => 'Nominal bebas', 'nominal' => 0, 'ket' => 'Tentukan sendiri nominal bantuan pemberdayaan ekonomi Anda.'],
            ],
        ],
        'bantuan-kemanusiaan' => [
            'kategori' => 'DANA_SOSIAL',
            'paket' => [
                ['nama' => 'Paket kebutuhan pokok', 'nominal' => 100000, 'ket' => 'Membantu kebutuhan pokok bagi warga yang terdampak musibah.'],
                ['nama' => 'Perlengkapan darurat', 'nominal' => 250000, 'ket' => 'Membantu perlengkapan darurat bagi korban bencana.'],
                ['nama' => 'Tempat tinggal sementara', 'nominal' => 1000000, 'ket' => 'Membantu tempat tinggal sementara bagi warga yang kehilangan rumah.'],
                ['nama' => 'Nominal bebas', 'nominal' => 0, 'ket' => 'Tentukan sendiri nominal bantuan kemanusiaan Anda.'],
            ],
        ],
    ],

    'dampak' => [
        'Dana pendidikan disalurkan untuk membantu anak dari keluarga prasejahtera agar tetap bersekolah.',
        'Dana pemberdayaan ekonomi dipakai untuk modal usaha kecil di sekitar lingkungan perusahaan.',
        'Bantuan kesehatan dan kemanusiaan diberikan cepat kepada warga yang terdampak musibah.',
    ],

    'alur' => [
        ['judul' => 'Konsultasi awal', 'isi' => 'Kami memahami kebutuhan donatur, jenis dana yang ingin ditunaikan, dan program yang dituju.'],
        ['judul' => 'Penghimpunan dana', 'isi' => 'Dana zakat, infak, dan sedekah dihimpun melalui kanal perbankan resmi dan e-wallet.'],
        ['judul' => 'Verifikasi penerima', 'isi' => 'Calon penerima manfaat didata dan diverifikasi agar bantuan tepat sasaran sesuai syariat.'],
        ['judul' => 'Penyaluran program', 'isi' => 'Dana disalurkan sesuai program pendidikan, kesehatan, ekonomi, dakwah, atau kemanusiaan.'],
        ['judul' => 'Pendampingan', 'isi' => 'Penerima manfaat didampingi agar bantuan memberi dampak yang berkelanjutan.'],
        ['judul' => 'Pelaporan transparan', 'isi' => 'Laporan penghimpunan dan penyaluran disampaikan kepada donatur dan pemangku kepentingan secara berkala.'],
    ],

    'tim' => [
        ['nama' => 'Pengurus UPZ', 'jabatan' => 'Ketua UPZ', 'desc' => 'Mengoordinasikan seluruh kegiatan penghimpunan dan penyaluran dana.'],
        ['nama' => 'Pengurus UPZ', 'jabatan' => 'Sekretaris', 'desc' => 'Mengelola administrasi, laporan, dan dokumentasi kegiatan.'],
        ['nama' => 'Pengurus UPZ', 'jabatan' => 'Bendahara', 'desc' => 'Mencatat dana masuk dan keluar secara tertib dan akuntabel.'],
        ['nama' => 'Pengurus UPZ', 'jabatan' => 'Divisi Penghimpunan', 'desc' => 'Mengedukasi dan melayani insan perusahaan yang ingin berdonasi.'],
        ['nama' => 'Pengurus UPZ', 'jabatan' => 'Divisi Pendistribusian', 'desc' => 'Memverifikasi penerima manfaat dan menyalurkan bantuan.'],
        ['nama' => 'Pengurus UPZ', 'jabatan' => 'Divisi Humas', 'desc' => 'Mengelola informasi publik dan komunikasi dengan para donatur.'],
    ],

    'testimoni_statis' => [
        ['isi' => 'Alhamdulillah, penyaluran zakat penghasilan melalui UPZ BAZNAS Petrokimia Gresik sangat mudah, cepat, dan laporannya transparan.', 'nama' => 'Ir. Bambang Triyono', 'peran' => 'Karyawan PT Petrokimia Gresik'],
        ['isi' => 'Bantuan pendidikan dari UPZ sangat meringankan keluarga kami. Anak saya kini dapat terus melanjutkan kuliah dengan lancar.', 'nama' => 'Ibu Siti Aminah', 'peran' => 'Penerima Beasiswa UPZ'],
        ['isi' => 'Sinergi penyaluran bantuan kemanusiaan bersama UPZ selalu sigap dan terkoordinasi dengan sangat baik di lapangan.', 'nama' => 'Rahmat Hidayat', 'peran' => 'Relawan Kemanusiaan'],
    ],
];
