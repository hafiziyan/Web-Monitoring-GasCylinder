<?php

namespace App\Services;

use App\Models\Cylinder;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class CylinderQrCode
{
    /**
     * Generate gambar QR (SVG) yang berisi qr_token saja.
     * QR bersifat permanen karena tidak memuat data mutable.
     */
    public function svg(string $qrToken, int $size = 220): string
    {
        $builder = new Builder(
            writer: new SvgWriter,
            data: $qrToken,
            size: $size,
            margin: 10,
        );

        return $builder->build()->getString();
    }

    /**
     * Generate token unik format CYL-XXXXXXXX.
     */
    public function generateUniqueToken(): string
    {
        do {
            $token = 'CYL-'.Str::upper(Str::random(8));
        } while (Cylinder::where('qr_token', $token)->exists());

        return $token;
    }
}
