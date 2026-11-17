<?php

declare(strict_types=1);

namespace App\Enums;

enum PeristiwaAudit: string
{
    case Masuk = 'masuk';
    case MasukGagal = 'masuk-gagal';
    case Keluar = 'keluar';
    case SesiDicabut = 'sesi-dicabut';
    case SesiDipakaiUlang = 'sesi-dipakai-ulang';
    case PeranDiubah = 'peran-diubah';
    case StatusPesanan = 'status-pesanan';
    case DanaDilepas = 'dana-dilepas';
    case PencairanDiajukan = 'pencairan-diajukan';
    case PencairanDiputuskan = 'pencairan-diputuskan';
    case StokDisesuaikan = 'stok-disesuaikan';
    case UjiBalapan = 'uji-balapan';

    public function keterangan(): string
    {
        return match ($this) {
            self::Masuk => 'Pengguna berhasil masuk',
            self::MasukGagal => 'Percobaan masuk gagal',
            self::Keluar => 'Pengguna keluar dari sesi',
            self::SesiDicabut => 'Sesi pengguna dicabut',
            self::SesiDipakaiUlang => 'Kunci sesi yang sudah dicabut dipakai ulang',
            self::PeranDiubah => 'Peran pengguna diubah oleh admin',
            self::StatusPesanan => 'Status pesanan berubah',
            self::DanaDilepas => 'Dana escrow dilepas ke penjual',
            self::PencairanDiajukan => 'Pengajuan pencairan saldo oleh penjual',
            self::PencairanDiputuskan => 'Keputusan persetujuan atau penolakan pencairan',
            self::StokDisesuaikan => 'Penyesuaian stok produk',
            self::UjiBalapan => 'Uji balapan audit serempak',
        };
    }
}
